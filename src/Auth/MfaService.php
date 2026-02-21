<?php
// src/Auth/MfaService.php

require_once __DIR__ . '/../Database.php';

class MfaService {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::pdo();
  }

  public function ensureSecret(int $userId): string {
    $stmt = $this->pdo->prepare("SELECT secret_base32 FROM mfa_totp WHERE usuario_id=:u LIMIT 1");
    $stmt->execute([':u'=>$userId]);
    $row = $stmt->fetch();
    if ($row) return (string)$row['secret_base32'];

    $secret = $this->randomBase32(32);
    $stmt = $this->pdo->prepare("INSERT INTO mfa_totp (usuario_id, secret_base32) VALUES (:u, :s)");
    $stmt->execute([':u'=>$userId, ':s'=>$secret]);
    return $secret;
  }

  public function verifyCode(int $userId, string $code): bool {
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) return false;

    $secret = $this->ensureSecret($userId);
    $now = time();

    for ($i=-1; $i<=1; $i++) {
      if (hash_equals($this->totp($secret, $now + ($i*30)), $code)) return true;
    }
    return false;
  }

  public function provisioningUri(string $email, string $secret, string $issuer='AxisBeauty'): string {
    $issuerEnc = rawurlencode($issuer);
    $label = rawurlencode($issuer . ':' . $email);
    return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEnc}&digits=6&period=30";
  }

  private function totp(string $base32Secret, int $time): string {
    $key = $this->base32Decode($base32Secret);
    $counter = intdiv($time, 30);

    $binCounter = pack('N*', 0) . pack('N*', $counter);
    $hash = hash_hmac('sha1', $binCounter, $key, true);

    $offset = ord(substr($hash, -1)) & 0x0F;
    $part = substr($hash, $offset, 4);
    $value = unpack('N', $part)[1] & 0x7FFFFFFF;
    $mod = $value % 1000000;

    return str_pad((string)$mod, 6, '0', STR_PAD_LEFT);
  }

  private function randomBase32(int $length): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $out = '';
    for ($i=0; $i<$length; $i++) {
      $out .= $alphabet[random_int(0, strlen($alphabet)-1)];
    }
    return $out;
  }

  private function base32Decode(string $b32): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $b32 = preg_replace('/[^A-Z2-7]/', '', $b32);

    $bits = '';
    for ($i=0; $i<strlen($b32); $i++) {
      $v = strpos($alphabet, $b32[$i]);
      $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
    }

    $out = '';
    for ($i=0; $i+8<=strlen($bits); $i+=8) {
      $out .= chr(bindec(substr($bits, $i, 8)));
    }
    return $out;
  }
}
