<?php
// src/Auth/AuthService.php

require_once __DIR__ . '/UserRepository.php';
require_once __DIR__ . '/LoginAttemptService.php';
require_once __DIR__ . '/RememberMeService.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/../Database.php';

class AuthService {
  private array $cfg;
  private UserRepository $users;
  private LoginAttemptService $attempts;
  private RememberMeService $remember;
  private SessionManager $sessions;
  private PDO $pdo;

  public function __construct(array $cfg) {
    $this->cfg = $cfg;
    $this->pdo = Database::pdo();
    $this->users = new UserRepository();
    $this->attempts = new LoginAttemptService($cfg);
    $this->remember = new RememberMeService($cfg);
    $this->sessions = new SessionManager($cfg);
    $this->sessions->start();
  }

  public function user(): ?array {
    if (!empty($_SESSION['user_id'])) {
      $this->sessions->touch();
      return $this->users->findById((int)$_SESSION['user_id']);
    }

    $uid = $this->remember->consume();
    if ($uid) {
      $this->sessions->regenerate();
      $_SESSION['user_id'] = $uid;
      $this->sessions->bindSessionToUser($uid);
      $this->audit($uid, 'auth.remember_me_login', []);
      return $this->users->findById($uid);
    }

    return null;
  }

  public function login(string $email, string $password, bool $rememberMe=false): array {
    $email = strtolower(trim($email));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $lock = $this->attempts->isLocked($email, $ip);
    if ($lock['locked']) {
      $this->attempts->record($email, $ip, $ua, false, 'locked');
      return ['ok'=>false, 'error'=>"Muitas tentativas. Aguarde {$lock['wait_seconds']}s."];
    }

    $u = $this->users->findByEmail($email);
    if (!$u) {
      $this->attempts->record($email, $ip, $ua, false, 'invalid_user');
      return ['ok'=>false, 'error'=>'Usuário ou senha inválidos.'];
    }

    // Cadastro público: bloquear login até validar email
    if (empty($u['email_verificado_em'])) {
      $this->attempts->record($email, $ip, $ua, false, 'email_not_verified');
      return ['ok'=>false, 'error'=>'Confirme seu email para ativar a conta.'];
    }

    if ($u['status'] !== 'ativo') {
      $this->attempts->record($email, $ip, $ua, false, 'status_not_active');
      return ['ok'=>false, 'error'=>'Conta não está ativa.'];
    }

    if (!password_verify($password, $u['senha_hash'])) {
      $this->attempts->record($email, $ip, $ua, false, 'bad_password');
      $this->audit((int)$u['id'], 'auth.login_failed', ['reason'=>'bad_password']);
      return ['ok'=>false, 'error'=>'Usuário ou senha inválidos.'];
    }

    if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) {
      $new = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $this->pdo->prepare("UPDATE usuarios SET senha_hash=:h WHERE id=:id");
      $stmt->execute([':h'=>$new, ':id'=>(int)$u['id']]);
    }

    $this->sessions->regenerate();
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['mfa_pending'] = (int)$u['mfa_ativo'] === 1 ? 1 : 0;

    $this->sessions->bindSessionToUser((int)$u['id']);
    $this->attempts->record($email, $ip, $ua, true, null);
    $this->audit((int)$u['id'], 'auth.login_success', []);

    if ($rememberMe) {
      $this->remember->issue((int)$u['id']);
      $this->audit((int)$u['id'], 'auth.remember_me_issued', []);
    }

    return ['ok'=>true, 'mfa_required'=> ((int)$u['mfa_ativo']===1)];
  }

  public function requireMfaVerified(): bool {
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['mfa_pending']) && (int)$_SESSION['mfa_pending'] === 1) {
      return false;
    }
    return true;
  }

  public function markMfaVerified(): void {
    $_SESSION['mfa_pending'] = 0;
  }

  public function logout(): void {
    if (!empty($_SESSION['user_id'])) {
      $this->audit((int)$_SESSION['user_id'], 'auth.logout', []);
    }
    $this->remember->clearCookie();
    $this->sessions->revokeCurrent();
  }

  private function audit(?int $userId, string $acao, array $detalhes): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $this->pdo->prepare("
      INSERT INTO audit_logs (usuario_id, acao, ip, user_agent, detalhes)
      VALUES (:uid, :acao, :ip, :ua, :det)
    ");
    $stmt->execute([
      ':uid'=>$userId,
      ':acao'=>$acao,
      ':ip'=>$ip,
      ':ua'=>$ua,
      ':det'=>json_encode($detalhes, JSON_UNESCAPED_UNICODE)
    ]);
  }
}
