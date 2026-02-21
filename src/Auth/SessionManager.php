<?php
// src/Auth/SessionManager.php

require_once __DIR__ . '/../Database.php';

class SessionManager {
  private PDO $pdo;
  private array $cfg;

  public function __construct(array $cfg) {
    $this->pdo = Database::pdo();
    $this->cfg = $cfg;
  }

  public function start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $sec = $this->cfg['security'];
    $auth = $this->cfg['auth'];

    session_name($auth['session_name']);

    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'domain' => '',
      'secure' => (bool)$sec['cookie_secure'],
      'httponly' => true,
      'samesite' => $sec['cookie_samesite'] ?? 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_start();
  }

  public function regenerate(): void {
    session_regenerate_id(true);
  }

  public function bindSessionToUser(int $userId): void {
    $sid = session_id();
    $ip = $this->ip();
    $ua = substr($this->ua(), 0, 255);

    $stmt = $this->pdo->prepare("
      INSERT INTO user_sessions (usuario_id, session_id, ip, user_agent)
      VALUES (:uid, :sid, :ip, :ua)
    ");
    $stmt->execute([':uid'=>$userId, ':sid'=>$sid, ':ip'=>$ip, ':ua'=>$ua]);
  }

  public function touch(): void {
    if (empty($_SESSION['user_id'])) return;
    $sid = session_id();

    $stmt = $this->pdo->prepare("
      UPDATE user_sessions SET ultimo_uso_em=NOW()
      WHERE session_id=:sid AND revogado_em IS NULL
    ");
    $stmt->execute([':sid'=>$sid]);
  }

  public function revokeCurrent(): void {
    $sid = session_id();
    $stmt = $this->pdo->prepare("UPDATE user_sessions SET revogado_em=NOW() WHERE session_id=:sid");
    $stmt->execute([':sid'=>$sid]);

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
  }

  private function ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  }

  private function ua(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
  }
}
