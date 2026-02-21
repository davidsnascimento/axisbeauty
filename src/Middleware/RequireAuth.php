<?php
// src/Middleware/RequireAuth.php

require_once __DIR__ . '/../Auth/AuthService.php';
require_once __DIR__ . '/../Security/SecurityHeaders.php';

class RequireAuth {
  public static function handle(array $cfg): array {
    SecurityHeaders::apply();
    $auth = new AuthService($cfg);
    $user = $auth->user();

    if (!$user) {
      header('Location: /axisbeauty/public/login.php');
      exit;
    }

    if (!$auth->requireMfaVerified()) {
      header('Location: /axisbeauty/public/mfa.php');
      exit;
    }

    return $user;
  }
}
