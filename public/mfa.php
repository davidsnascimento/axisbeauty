<?php
require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Security/Csrf.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Auth/MfaService.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';

SecurityHeaders::apply();
$cfg = require __DIR__ . '/../config/config.php';

$session = new SessionManager($cfg);
$session->start();

$auth = new AuthService($cfg);
$user = $auth->user();

if (!$user) {
  header('Location: /axisbeauty/public/login.php');
  exit;
}

if (empty($_SESSION['mfa_pending']) || (int)$_SESSION['mfa_pending'] !== 1) {
  header('Location: /axisbeauty/public/dashboard.php');
  exit;
}

$mfa = new MfaService();
$error = null;
$secret = $mfa->ensureSecret((int)$user['id']);
$uri = $mfa->provisioningUri((string)$user['email'], $secret, 'AxisBeauty');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::verify($_POST['csrf'] ?? null)) {
    $error = 'CSRF inválido.';
  } else {
    $code = (string)($_POST['code'] ?? '');
    if ($mfa->verifyCode((int)$user['id'], $code)) {
      $auth->markMfaVerified();
      header('Location: /axisbeauty/public/dashboard.php');
      exit;
    } else {
      $error = 'Código inválido.';
    }
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Verificação 2FA</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell">
  <div class="brand-panel">
    <div class="logo">AB</div>
    <h1>AxisBeauty</h1>
    <p>Proteja sua conta com autenticação em duas etapas. Isso reduz drasticamente riscos de invasão.</p>
    <div class="brand-badges">
      <span class="badge">Plano Free permanente</span>
      <span class="badge">Segurança nível empresa</span>
      <span class="badge">SaaS escalável</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-title">Verificação 2FA</div>

    <?php if ($error): ?>
  <div class="notice error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="notice" style="background: rgba(255,255,255,.04);">
  <b>Primeiro acesso no 2FA?</b><br>
  No Google Authenticator / Microsoft Authenticator, adicione uma conta com esta chave:
  <div style="margin-top:8px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;">
    <?= htmlspecialchars($secret) ?>
  </div>
</div>

<form method="post">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

  <div class="form-group">
    <label for="code">Código (6 dígitos)</label>
    <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required autocomplete="one-time-code">
  </div>

  <button class="btn" type="submit">Confirmar</button>

  <div class="helper">
    Se o código não aceitar, aguarde trocar e tente novamente.
  </div>
</form>


    <div class="divider"></div>
    <div class="links"><a href="/axisbeauty/public/logout.php">Sair</a></div>
  </div>
</div>

</body>
</html>
