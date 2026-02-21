<?php
require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Auth/EmailVerificationService.php';
require_once __DIR__ . '/../src/Database.php';

SecurityHeaders::apply();
$cfg = require __DIR__ . '/../config/config.php';

$token = (string)($_GET['token'] ?? '');
$error = null;
$ok = null;

$svc = new EmailVerificationService();
$pdo = Database::pdo();

if (!$token) {
  $error = 'Token ausente.';
} else {
  $userId = $svc->validate($token);
  if (!$userId) {
    $error = 'Token inválido ou expirado.';
  } else {
    // ativa usuário
    $stmt = $pdo->prepare("UPDATE usuarios SET email_verificado_em=NOW(), status='ativo' WHERE id=:id");
    $stmt->execute([':id'=>$userId]);
    $svc->markUsed($token);
    $ok = 'Email confirmado! Agora você já pode fazer login.';
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Verificar Email</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell" style="grid-template-columns:1fr;">
  <div class="auth-panel" style="border-radius:18px;">
    <div class="auth-title">Verificação de Email</div>

    <?php if ($error): ?>
      <div class="notice error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="notice ok"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <div class="links">
      <a href="/axisbeauty/public/login.php">Ir para o login</a>
    </div>
  </div>
</div>

</body>
</html>
