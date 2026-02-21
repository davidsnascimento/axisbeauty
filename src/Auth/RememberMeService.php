<?php
// src/Auth/RememberMeService.php

require_once __DIR__ . '/../Database.php';

class RememberMeService {
  private PDO $pdo;
  private array $cfg;

  public function __construct(array $cfg) {
    $this->pdo = Database::pdo();
    $this->cfg = $cfg;
  }

  public function issue(int $userId): void {
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);

    $days = (int)$this->cfg['auth']['remember_me_days'];
    $expires = date('Y-m-d H:i:s', time() + $days*86400);

    $stmt = $this->pdo->prepare("
      INSERT INTO remember_tokens (usuario_id, selector, token_hash, expires_at)
      VALUES (:uid, :sel, :hash, :exp)
    ");
    $stmt->execute([':uid'=>$userId, ':sel'=>$selector, ':hash'=>$tokenHash, ':exp'=>$expires]);

    $cookie = $selector . ':' . $validator;
    $sec = $this->cfg['security'];

    setcookie('axisbeauty_remember', $cookie, [
      'expires' => time() + $days*86400,
      'path' => '/',
      'secure' => (bool)$sec['cookie_secure'],
      'httponly' => true,
      'samesite' => $sec['cookie_samesite'] ?? 'Lax',
    ]);
  }

  public function consume(): ?int {
    if (empty($_COOKIE['axisbeauty_remember'])) return null;

    $parts = explode(':', $_COOKIE['axisbeauty_remember'], 2);
    if (count($parts) != 2) return null;

    [$selector, $validator] = $parts;
    if (!$selector || !$validator) return null;

    $stmt = $this->pdo->prepare("
      SELECT * FROM remember_tokens
      WHERE selector=:sel AND revogado_em IS NULL AND expires_at > NOW()
      LIMIT 1
    ");
    $stmt->execute([':sel'=>$selector]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $calc = hash('sha256', $validator);
    if (!hash_equals($row['token_hash'], $calc)) {
      $this->revokeAll((int)$row['usuario_id']);
      $this->clearCookie();
      return null;
    }

    $userId = (int)$row['usuario_id'];
    $this->revokeSelector($selector);
    $this->issue($userId);
    return $userId;
  }

  public function revokeAll(int $userId): void {
    $stmt = $this->pdo->prepare("UPDATE remember_tokens SET revogado_em=NOW() WHERE usuario_id=:uid");
    $stmt->execute([':uid'=>$userId]);
  }

  public function revokeSelector(string $selector): void {
    $stmt = $this->pdo->prepare("UPDATE remember_tokens SET revogado_em=NOW() WHERE selector=:s");
    $stmt->execute([':s'=>$selector]);
  }

  public function clearCookie(): void {
    $sec = $this->cfg['security'];
    setcookie('axisbeauty_remember', '', [
      'expires' => time()-3600,
      'path' => '/',
      'secure' => (bool)$sec['cookie_secure'],
      'httponly' => true,
      'samesite' => $sec['cookie_samesite'] ?? 'Lax',
    ]);
  }
}
