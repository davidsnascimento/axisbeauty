<?php
// src/Auth/UserRepository.php
//
// Repositório de usuários.
// Arquitetura SaaS multi-tenant:
// - Todo usuário pertence a uma conta (conta_id)
// - Controle de roles via usuario_roles
// - Email verification suportado

require_once __DIR__ . '/../Database.php';

class UserRepository {

    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::pdo();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM usuarios WHERE email = :e LIMIT 1"
        );
        $stmt->execute([':e' => $email]);
        $u = $stmt->fetch();
        return $u ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM usuarios WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $u = $stmt->fetch();
        return $u ?: null;
    }

    /**
     * Cria usuário (padrão: status pendente até validar email)
     */
    public function create(
        int $contaId,
        string $nome,
        string $email,
        string $senhaHash,
        string $status = 'pendente'
    ): int {

        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios
                (conta_id, nome, email, senha_hash, status, email_verificado_em)
            VALUES
                (:cid, :n, :e, :h, :st, NULL)
        ");

        $stmt->execute([
            ':cid' => $contaId,
            ':n'   => $nome,
            ':e'   => $email,
            ':h'   => $senhaHash,
            ':st'  => $status
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function setMfaActive(int $userId, bool $active): void {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios SET mfa_ativo = :a WHERE id = :id"
        );
        $stmt->execute([
            ':a'  => $active ? 1 : 0,
            ':id' => $userId
        ]);
    }

    /**
     * Define uma role para o usuário
     * (v1: apenas uma role por usuário)
     */
    public function assignRoleByName(int $userId, string $roleName): void {

        $this->pdo->beginTransaction();

        try {

            // remove roles atuais
            $stmt = $this->pdo->prepare(
                "DELETE FROM usuario_roles WHERE usuario_id = :u"
            );
            $stmt->execute([':u' => $userId]);

            // busca id da role
            $stmt = $this->pdo->prepare(
                "SELECT id FROM roles WHERE nome = :n LIMIT 1"
            );
            $stmt->execute([':n' => $roleName]);
            $roleId = $stmt->fetchColumn();

            if (!$roleId) {
                throw new Exception("Role não encontrada: " . $roleName);
            }

            // insere vínculo
            $stmt = $this->pdo->prepare(
                "INSERT INTO usuario_roles (usuario_id, role_id)
                 VALUES (:u, :r)"
            );
            $stmt->execute([
                ':u' => $userId,
                ':r' => (int)$roleId
            ]);

            $this->pdo->commit();

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Lista usuários da mesma conta (isolamento multi-tenant)
     */
    public function listByConta(int $contaId): array {

        $stmt = $this->pdo->prepare("
            SELECT id, nome, email, status, mfa_ativo,
                   email_verificado_em, criado_em
            FROM usuarios
            WHERE conta_id = :cid
            ORDER BY id DESC
        ");

        $stmt->execute([':cid' => $contaId]);

        return $stmt->fetchAll() ?: [];
    }
}