<?php
$cfg = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Middleware/RequireAuth.php';
require_once __DIR__ . '/../src/Middleware/RequirePermission.php';

$user = RequireAuth::handle($cfg);
RequirePermission::handle((int)$user['id'], 'dashboard.ver');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Dashboard</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell" style="grid-template-columns: 1fr;">
  <div class="auth-panel" style="border-radius: 18px;">
    <div class="auth-title">Dashboard</div>

    <div class="notice ok">
      Logado como: <b><?= htmlspecialchars($user['nome']) ?></b> (<?= htmlspecialchars($user['email']) ?>)
    </div>

    <div class="notice" style="background: rgba(255,255,255,.04);">
      <b>Conta ID:</b> <?= htmlspecialchars((string)($user['conta_id'] ?? '')) ?><br>
      <b>MFA:</b> <?= ((int)$user['mfa_ativo']===1 ? 'ativo' : 'inativo') ?>
    </div>

    <div class="links">
      <a href="/axisbeauty/public/users.php">Usuários</a> •
      <a href="/axisbeauty/public/logout.php">Sair</a>
    </div>
  </div>
</div>

</body>
</html>
