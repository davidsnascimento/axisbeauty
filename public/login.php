<?php
require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Security/Csrf.php';
require_once __DIR__ . '/../src/Security/Validator.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';

SecurityHeaders::apply();
$cfg = require __DIR__ . '/../config/config.php';

$session = new SessionManager($cfg);
$session->start();

$auth = new AuthService($cfg);

// prevent access if already logged
$existing = $auth->user();
if ($existing) {
  if (!$auth->requireMfaVerified()) {
    header('Location: /axisbeauty/public/mfa.php');
    exit;
  }
  header('Location: /axisbeauty/public/dashboard.php');
  exit;
}


$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::verify($_POST['csrf'] ?? null)) {
    $error = 'CSRF inválido.';
  } else {
    $email = (string)($_POST['email'] ?? '');
    $pass = (string)($_POST['senha'] ?? '');
    $remember = !empty($_POST['remember']);

    $res = $auth->login($email, $pass, $remember);
    if (!$res['ok']) {
      $error = $res['error'];
    } else {
      if (!empty($res['mfa_required'])) {
        header('Location: /axisbeauty/public/mfa.php');
        exit;
      }
      header('Location: /axisbeauty/public/dashboard.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Acessar conta</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell">
  <div class="brand-panel">
    <div class="logo">AB</div>
    <h1>AxisBeauty</h1>
    <p>Gestão inteligente para o seu salão. Controle agenda, clientes, profissionais e financeiro com segurança nível empresa.</p>
    <div class="brand-badges">
      <span class="badge">Plano Free permanente</span>
      <span class="badge">Segurança nível empresa</span>
      <span class="badge">SaaS escalável</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-title">Acessar conta</div>

    <?php if ($error): ?>
  <div class="notice error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

  <div class="form-group">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" placeholder="voce@exemplo.com" required autocomplete="username">
  </div>

  <div class="form-group">
    <label for="senha">Senha</label>
    <input id="senha" name="senha" type="password" placeholder="••••••••••" required autocomplete="current-password">
  </div>

  <label class="check">
    <input type="checkbox" name="remember" value="1">
    Manter conectado
  </label>

  <button class="btn" type="submit">Entrar</button>

  <div class="helper">
    Dica: se você estiver em um computador compartilhado, não marque “manter conectado”.
  </div>
</form>


    <div class="divider"></div>
    <div class="links"><a href="/axisbeauty/public/forgot_password.php">Esqueci minha senha</a> •
<a href="/axisbeauty/public/register.php">Criar conta</a> •
<a href="/axisbeauty/public/resend_verification.php">Reenviar verificação</a></div>
  </div>
</div>

</body>
</html>
