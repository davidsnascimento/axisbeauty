<?php
// src/Auth/PasswordResetService.php

require_once __DIR__ . '/../Database.php';

class PasswordResetService {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::pdo();
  }

  public function create(int $userId): string {
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $this->pdo->prepare("
      INSERT INTO password_resets (usuario_id, token_hash, expires_at)
      VALUES (:uid, :h, :exp)
    ");
    $stmt->execute([':uid'=>$userId, ':h'=>$hash, ':exp'=>$expires]);

    return $token;
  }

  public function validate(string $token): ?int {
    $hash = hash('sha256', $token);
    $stmt = $this->pdo->prepare("
      SELECT * FROM password_resets
      WHERE token_hash=:h AND usado_em IS NULL AND expires_at > NOW()
      ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([':h'=>$hash]);
    $row = $stmt->fetch();
    return $row ? (int)$row['usuario_id'] : null;
  }

  public function markUsed(string $token): void {
    $hash = hash('sha256', $token);
    $stmt = $this->pdo->prepare("UPDATE password_resets SET usado_em=NOW() WHERE token_hash=:h");
    $stmt->execute([':h'=>$hash]);
  }
}
