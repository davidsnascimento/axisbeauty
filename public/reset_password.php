<?php
// prevent access if already logged

require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Security/Csrf.php';
require_once __DIR__ . '/../src/Security/Validator.php';
require_once __DIR__ . '/../src/Auth/PasswordResetService.php';
\1
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Database.php';

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


$token = (string)($_GET['token'] ?? '');
$error = null;
$ok = null;

$resets = new PasswordResetService();
$pdo = Database::pdo();

$userId = $token ? $resets->validate($token) : null;

if (!$userId) {
  $error = 'Token inválido ou expirado.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::verify($_POST['csrf'] ?? null)) {
    $error = 'CSRF inválido.';
  } else {
    $senha = (string)($_POST['senha'] ?? '');
    $passErrors = Validator::strongPassword($senha);
    if ($passErrors) {
      $error = implode(' ', $passErrors);
    } else {
      $hash = password_hash($senha, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash=:h WHERE id=:id");
      $stmt->execute([':h'=>$hash, ':id'=>$userId]);
      $resets->markUsed($token);
      $ok = 'Senha atualizada. Faça login.';
    }
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Definir nova senha</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell">
  <div class="brand-panel">
    <div class="logo">AB</div>
    <h1>AxisBeauty</h1>
    <p>Crie uma nova senha para sua conta. O link expira por segurança.</p>
    <div class="brand-badges">
      <span class="badge">Plano Free permanente</span>
      <span class="badge">Segurança nível empresa</span>
      <span class="badge">SaaS escalável</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-title">Definir nova senha</div>

    <?php if ($error): ?>
  <div class="notice error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="notice ok"><?= htmlspecialchars($ok) ?></div>
<?php endif; ?>

<?php if ($userId && !$ok): ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

  <div class="form-group">
    <label for="senha">Nova senha</label>
    <input id="senha" name="senha" type="password" placeholder="Crie uma senha forte" required autocomplete="new-password">
  </div>

  <button class="btn" type="submit">Salvar nova senha</button>

  <div class="helper">
    Dica: use uma senha única e forte. Evite repetir senhas antigas.
  </div>
</form>
<?php endif; ?>


    <div class="divider"></div>
    <div class="links"><a href="/axisbeauty/public/login.php">Voltar ao login</a></div>
  </div>
</div>

</body>
</html>
