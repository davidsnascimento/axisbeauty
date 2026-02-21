<?php
require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Security/Csrf.php';
require_once __DIR__ . '/../src/Security/Validator.php';
require_once __DIR__ . '/../src/Auth/ContaRepository.php';
require_once __DIR__ . '/../src/Auth/UserRepository.php';
require_once __DIR__ . '/../src/Auth/EmailVerificationService.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

SecurityHeaders::apply();
$cfg = require __DIR__ . '/../config/config.php';

$session = new SessionManager($cfg);
$session->start();

$auth = new AuthService($cfg);
$existing = $auth->user();
if ($existing) {
  if (!$auth->requireMfaVerified()) {
    header('Location: /axisbeauty/public/mfa.php');
    exit;
  }
  header('Location: /axisbeauty/public/dashboard.php');
  exit;
}

$contas = new ContaRepository();
$users = new UserRepository();
$verify = new EmailVerificationService();

$error = null;
$ok = null;
$verifyLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::verify($_POST['csrf'] ?? null)) {
    $error = 'CSRF inválido.';
  } else {
    $nome = trim((string)($_POST['nome'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $senha = (string)($_POST['senha'] ?? '');

    if ($nome === '') $error = 'Nome obrigatório.';
    elseif (!Validator::email($email)) $error = 'Email inválido.';
    else {
      $passErrors = Validator::strongPassword($senha);
      if ($passErrors) $error = implode(' ', $passErrors);
      else {
        if ($users->findByEmail($email)) {
          $error = 'Email já cadastrado.';
        } else {
          // Multi-tenant: cria conta (FREE) + primeiro usuário (pendente) + dono_conta
          $contaId = $contas->create("Conta de {$nome}", "FREE");
          $hash = password_hash($senha, PASSWORD_DEFAULT);

          // status pendente até validar email
          $id = $users->create($contaId, $nome, $email, $hash, 'pendente');
          $users->assignRoleByName($id, 'dono_conta');

          // cria token de verificação
          $token = $verify->create($id);
          $verifyLink = "/axisbeauty/public/verify_email.php?token=" . urlencode($token);

          $ok = "Conta criada. Agora confirme seu email para ativar.";
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Criar conta</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell">
  <div class="brand-panel">
    <div class="logo">AB</div>
    <h1>AxisBeauty</h1>
    <p>Cadastro público com validação de email. Você começa no plano Free permanente e evolui no seu ritmo.</p>
    <div class="brand-badges">
      <span class="badge">Plano Free permanente</span>
      <span class="badge">Segurança nível empresa</span>
      <span class="badge">SaaS escalável</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-title">Criar conta</div>

    <?php if ($error): ?>
      <div class="notice error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="notice ok"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

      <div class="form-group">
        <label for="nome">Nome</label>
        <input id="nome" name="nome" placeholder="Seu nome" required autocomplete="name">
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="voce@exemplo.com" required autocomplete="email">
      </div>

      <div class="form-group">
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" placeholder="Crie uma senha forte" required autocomplete="new-password">
      </div>

      <button class="btn" type="submit">Criar conta</button>

      <div class="helper">
        Após o cadastro, você precisa confirmar o email para ativar a conta.
      </div>
    </form>

    <?php if ($verifyLink): ?>
      <div class="notice" style="margin-top:14px;background: rgba(255,255,255,.04);">
        <b>DEV:</b> link de verificação gerado (em produção, isso vai por email):<br>
        <div style="margin-top:8px; word-break: break-all;">
          <a href="<?= htmlspecialchars($verifyLink) ?>"><?= htmlspecialchars($verifyLink) ?></a>
        </div>
      </div>
    <?php endif; ?>

    <div class="divider"></div>
    <div class="links"><a href="/axisbeauty/public/login.php">Voltar ao login</a></div>
  </div>
</div>

</body>
</html>
