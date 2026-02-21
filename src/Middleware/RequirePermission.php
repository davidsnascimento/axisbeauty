<?php
// src/Middleware/RequirePermission.php
//
// Middleware simples para proteger páginas por permissão.
// Uso:
//   $cfg = require ...
//   $user = RequireAuth::handle($cfg);
//   RequirePermission::handle((int)$user['id'], 'usuarios.gerenciar');

require_once __DIR__ . '/../RBAC/RbacService.php';

class RequirePermission {
  public static function handle(int $userId, string $permissionKey): void {
    $rbac = new RbacService();
    $rbac->enforce($userId, $permissionKey);
  }
}
