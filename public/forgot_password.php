<?php
// prevent access if already logged

require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Security/Csrf.php';
require_once __DIR__ . '/../src/Security/Validator.php';
require_once __DIR__ . '/../src/Auth/UserRepository.php';
require_once __DIR__ . '/../src/Auth/PasswordResetService.php';
\1
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


$repo = new UserRepository();
$resets = new PasswordResetService();

$error = null;
$link = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::verify($_POST['csrf'] ?? null)) {
    $error = 'CSRF inválido.';
  } else {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!Validator::email($email)) $error = 'Email inválido.';
    else {
      $u = $repo->findByEmail($email);
      if ($u) {
        $token = $resets->create((int)$u['id']);
        $link = "/axisbeauty/public/reset_password.php?token=" . urlencode($token);
      } else {
        $link = "/axisbeauty/public/login.php";
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
  <title>AxisBeauty - Recuperar senha</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell">
  <div class="brand-panel">
    <div class="logo">AB</div>
    <h1>AxisBeauty</h1>
    <p>Informe seu email para gerar um link seguro de redefinição. Se existir uma conta, você receberá o acesso.</p>
    <div class="brand-badges">
      <span class="badge">Plano Free permanente</span>
      <span class="badge">Segurança nível empresa</span>
      <span class="badge">SaaS escalável</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-title">Recuperar senha</div>

    <?php if ($error): ?>
  <div class="notice error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

  <div class="form-group">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" placeholder="voce@exemplo.com" required autocomplete="email">
  </div>

  <button class="btn" type="submit">Gerar link</button>
</form>

<?php if ($link): ?>
  <div class="notice ok" style="margin-top:14px;">
    Link gerado (em produção, isso vai por email):
    <div style="margin-top:8px; word-break: break-all;">
      <a href="<?= htmlspecialchars($link) ?>"><?= htmlspecialchars($link) ?></a>
    </div>
  </div>
<?php endif; ?>


    <div class="divider"></div>
    <div class="links"><a href="/axisbeauty/public/login.php">Voltar</a></div>
  </div>
</div>

</body>
</html>
