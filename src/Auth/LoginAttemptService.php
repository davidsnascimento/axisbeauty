<?php
// src/Auth/LoginAttemptService.php

require_once __DIR__ . '/../Database.php';

class LoginAttemptService {
  private PDO $pdo;
  private int $maxAttempts;
  private int $lockMinutes;

  public function __construct(array $cfg) {
    $this->pdo = Database::pdo();
    $this->maxAttempts = (int)$cfg['auth']['max_attempts_15min'];
    $this->lockMinutes = (int)$cfg['auth']['lock_minutes'];
  }

  public function isLocked(string $email, string $ip): array {
    $stmt = $this->pdo->prepare("
      SELECT COUNT(*) AS c
      FROM login_attempts
      WHERE sucesso=0 AND (email=:email OR ip=:ip)
        AND criado_em >= (NOW() - INTERVAL 15 MINUTE)
    ");
    $stmt->execute([':email'=>$email, ':ip'=>$ip]);
    $c = (int)($stmt->fetch()['c'] ?? 0);

    if ($c < $this->maxAttempts) return ['locked'=>false, 'wait_seconds'=>0];

    $stmt = $this->pdo->prepare("
      SELECT MAX(criado_em) AS last_fail
      FROM login_attempts
      WHERE sucesso=0 AND (email=:email OR ip=:ip)
        AND criado_em >= (NOW() - INTERVAL 15 MINUTE)
    ");
    $stmt->execute([':email'=>$email, ':ip'=>$ip]);
    $last = $stmt->fetch()['last_fail'] ?? null;

    if (!$last) return ['locked'=>true, 'wait_seconds'=>($this->lockMinutes*60)];

    $stmt = $this->pdo->query("SELECT TIMESTAMPDIFF(SECOND, '{$last}', NOW()) AS diff");
    $diff = (int)($stmt->fetch()['diff'] ?? 0);

    $wait = max(0, ($this->lockMinutes*60) - $diff);
    return ['locked'=>($wait>0), 'wait_seconds'=>$wait];
  }

  public function record(string $email, string $ip, string $ua, bool $success, ?string $reason=null): void {
    $stmt = $this->pdo->prepare("
      INSERT INTO login_attempts (email, ip, user_agent, sucesso, motivo)
      VALUES (:email, :ip, :ua, :s, :m)
    ");
    $stmt->execute([
      ':email'=>$email,
      ':ip'=>$ip,
      ':ua'=>substr($ua,0,255),
      ':s'=>$success ? 1 : 0,
      ':m'=>$reason
    ]);
  }
}
