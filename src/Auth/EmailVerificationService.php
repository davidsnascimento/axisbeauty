<?php
// src/Auth/EmailVerificationService.php
//
// Serviço de validação de email para cadastro público.
// Em produção, você envia o link por email.
// Em DEV, o sistema pode exibir o link na tela.

require_once __DIR__ . '/../Database.php';

class EmailVerificationService {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::pdo();
  }

  public function create(int $userId): string {
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 24*3600); // 24h

    $stmt = $this->pdo->prepare("
      INSERT INTO email_verifications (usuario_id, token_hash, expires_at)
      VALUES (:uid, :h, :exp)
    ");
    $stmt->execute([':uid'=>$userId, ':h'=>$hash, ':exp'=>$expires]);

    return $token;
  }

  public function validate(string $token): ?int {
    $hash = hash('sha256', $token);
    $stmt = $this->pdo->prepare("
      SELECT usuario_id
      FROM email_verifications
      WHERE token_hash=:h AND usado_em IS NULL AND expires_at > NOW()
      ORDER BY id DESC
      LIMIT 1
    ");
    $stmt->execute([':h'=>$hash]);
    $uid = $stmt->fetchColumn();
    return $uid ? (int)$uid : null;
  }

  public function markUsed(string $token): void {
    $hash = hash('sha256', $token);
    $stmt = $this->pdo->prepare("UPDATE email_verifications SET usado_em=NOW() WHERE token_hash=:h");
    $stmt->execute([':h'=>$hash]);
  }

  public function resend(int $userId): string {
    // Invalida tokens antigos pendentes (opcional)
    $stmt = $this->pdo->prepare("UPDATE email_verifications SET usado_em=NOW() WHERE usuario_id=:u AND usado_em IS NULL");
    $stmt->execute([':u'=>$userId]);
    return $this->create($userId);
  }
}
