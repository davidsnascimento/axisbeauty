<?php
$cfg = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Middleware/RequireAuth.php';
require_once __DIR__ . '/../src/Middleware/RequirePermission.php';
require_once __DIR__ . '/../src/Security/Csrf.php';
require_once __DIR__ . '/../src/Auth/UserRepository.php';
require_once __DIR__ . '/../src/Database.php';

$user = RequireAuth::handle($cfg);
RequirePermission::handle((int)$user['id'], 'usuarios.gerenciar');

$repo = new UserRepository();
$pdo = Database::pdo();

$error = null;
$ok = null;

$contaId = (int)($user['conta_id'] ?? 0);
if ($contaId <= 0) {
  $error = "Usuário sem conta vinculada (conta_id).";
}

$roles = ['dono_conta','gerente','recepcao','profissional','financeiro','super_admin'];

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Csrf::verify($_POST['csrf'] ?? null)) {
    $error = 'CSRF inválido.';
  } else {
    $targetId = (int)($_POST['user_id'] ?? 0);
    $role = (string)($_POST['role'] ?? '');

    if ($targetId <= 0) $error = 'Usuário inválido.';
    elseif (!in_array($role, $roles, true)) $error = 'Role inválida.';
    else {
      // Garantir que o usuário-alvo pertence à mesma conta (multi-tenant)
      $stmt = $pdo->prepare("SELECT conta_id FROM usuarios WHERE id=:id LIMIT 1");
      $stmt->execute([':id'=>$targetId]);
      $targetConta = (int)($stmt->fetchColumn() ?? 0);

      if ($targetConta !== $contaId) {
        $error = 'Ação negada (conta diferente).';
      } else {
        $repo->assignRoleByName($targetId, $role);
        $ok = 'Permissão/Role atualizada.';
      }
    }
  }
}

$rows = $error ? [] : $repo->listByConta($contaId);

// helper para role atual
function roleAtual(PDO $pdo, int $userId): string {
  $stmt = $pdo->prepare("
    SELECT r.nome
    FROM usuario_roles ur
    JOIN roles r ON r.id = ur.role_id
    WHERE ur.usuario_id=:u
    LIMIT 1
  ");
  $stmt->execute([':u'=>$userId]);
  return (string)($stmt->fetchColumn() ?? '');
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AxisBeauty - Usuários</title>
  <link rel="stylesheet" href="/axisbeauty/public/assets/style.css">
</head>
<body>

<div class="auth-shell" style="grid-template-columns: 1fr;">
  <div class="auth-panel" style="border-radius: 18px;">
    <div class="auth-title">Usuários (Conta <?= htmlspecialchars((string)$contaId) ?>)</div>

    <?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <div class="notice" style="background: rgba(255,255,255,.04);">
      Aqui você controla os acessos do time por cargos (roles). Nesta versão, cada usuário tem 1 role.
    </div>

    <div style="overflow:auto;border:1px solid rgba(255,255,255,.08);border-radius:14px;">
      <table style="width:100%;border-collapse:collapse;min-width:760px;">
        <thead>
          <tr style="text-align:left;background:rgba(255,255,255,.04);">
            <th style="padding:12px;">Nome</th>
            <th style="padding:12px;">Email</th>
            <th style="padding:12px;">MFA</th>
            <th style="padding:12px;">Role</th>
            <th style="padding:12px;">Ação</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <?php $curRole = roleAtual($pdo, (int)$r['id']); ?>
          <tr style="border-top:1px solid rgba(255,255,255,.06);">
            <td style="padding:12px;"><?= htmlspecialchars($r['nome']) ?></td>
            <td style="padding:12px;"><?= htmlspecialchars($r['email']) ?></td>
            <td style="padding:12px;"><?= ((int)$r['mfa_ativo']===1 ? 'ativo' : 'inativo') ?></td>
            <td style="padding:12px;">
              <form method="post" style="display:flex;gap:10px;align-items:center;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
                <select name="role" style="padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.35);color:#e5e7eb;">
                  <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role) ?>" <?= ($role===$curRole ? 'selected' : '') ?>>
                      <?= htmlspecialchars($role) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button class="btn" type="submit" style="width:auto;padding:10px 14px;">Salvar</button>
              </form>
            </td>
            <td style="padding:12px;color:rgba(229,231,235,.72);font-size:12px;">
              ID: <?= (int)$r['id'] ?>
            </td>
            <td style="padding:12px;">
              <!-- reservado para futuras ações (reset, bloquear, etc.) -->
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="links" style="margin-top:14px;">
      <a href="/axisbeauty/public/dashboard.php">Voltar</a> •
      <a href="/axisbeauty/public/logout.php">Sair</a>
    </div>
  </div>
</div>

</body>
</html>
