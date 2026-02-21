<?php
require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Security/Csrf.php';
require_once __DIR__ . '/../src/Security/Validator.php';
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

$users = new UserRepository();
$verify = new EmailVerificationService();

$error = null;
$ok = null;
$link = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::verify($_POST['csrf'] ?? null)) {
    $error = 'CSRF inválido.';
  } else {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!Validator::email($email)) {
      $error = 'Email inválido.';
    } else {
      $u = $users->findByEmail($email);
      // não revelar se existe ou não (evitar enumeração)
      if ($u && empty($u['email_verificado_em'])) {
        $token = $verify->resend((int)$u['id']);
        $link = "/axisbeauty/public/verify_email.php?token=" . urlencode($token);
      }
      $ok = 'Se existir uma conta pendente, geramos um novo link de verificação.';
    }
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Reenviar verificação</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell">
  <div class="brand-panel">
    <div class="logo">AB</div>
    <h1>AxisBeauty</h1>
    <p>Reenvio de verificação de email. Em produção, o link vai por email.</p>
    <div class="brand-badges">
      <span class="badge">Seguro</span>
      <span class="badge">Sem pressão</span>
      <span class="badge">Plano Free</span>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-title">Reenviar verificação</div>

    <?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">

      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="voce@exemplo.com" required autocomplete="email">
      </div>

      <button class="btn" type="submit">Reenviar</button>
    </form>

    <?php if ($link): ?>
      <div class="notice" style="margin-top:14px;background: rgba(255,255,255,.04);">
        <b>DEV:</b> link gerado:<br>
        <div style="margin-top:8px; word-break: break-all;">
          <a href="<?= htmlspecialchars($link) ?>"><?= htmlspecialchars($link) ?></a>
        </div>
      </div>
    <?php endif; ?>

    <div class="divider"></div>
    <div class="links"><a href="/axisbeauty/public/login.php">Voltar ao login</a></div>
  </div>
</div>

</body>
</html>
