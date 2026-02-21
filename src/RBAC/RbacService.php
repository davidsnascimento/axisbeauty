<?php
// src/RBAC/RbacService.php
//
// Serviço de RBAC (Role-Based Access Control) com isolamento por conta (multi-tenant).
//
// Regras:
// - Permissões são chaves como "clientes.criar".
// - Usuário possui roles via usuario_roles.
// - Roles possuem permissões via role_permissoes.
// - Se role = super_admin, bypass total.
// - Sempre considere conta_id para páginas/módulos de negócio (próximos módulos).

require_once __DIR__ . '/../Database.php';

class RbacService {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::pdo();
  }

  public function userRoles(int $userId): array {
    $stmt = $this->pdo->prepare("
      SELECT r.nome
      FROM usuario_roles ur
      JOIN roles r ON r.id = ur.role_id
      WHERE ur.usuario_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    return array_map(fn($x) => $x['nome'], $stmt->fetchAll() ?: []);
  }

  public function isSuperAdmin(int $userId): bool {
    $roles = $this->userRoles($userId);
    return in_array('super_admin', $roles, true);
  }

  public function userHasPermission(int $userId, string $permissionKey): bool {
    // Bypass para super_admin (suporte interno/empresa)
    if ($this->isSuperAdmin($userId)) return true;

    $stmt = $this->pdo->prepare("
      SELECT 1
      FROM usuario_roles ur
      JOIN role_permissoes rp ON rp.role_id = ur.role_id
      JOIN permissoes p ON p.id = rp.permissao_id
      WHERE ur.usuario_id = :uid
        AND p.chave = :chave
      LIMIT 1
    ");
    $stmt->execute([':uid' => $userId, ':chave' => $permissionKey]);
    return (bool)$stmt->fetchColumn();
  }

  public function enforce(int $userId, string $permissionKey): void {
    if (!$this->userHasPermission($userId, $permissionKey)) {
      http_response_code(403);
      echo "Acesso negado (403).";
      exit;
    }
  }
}
