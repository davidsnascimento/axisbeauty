<?php
require_once __DIR__ . '/../src/Security/SecurityHeaders.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';

SecurityHeaders::apply();
$cfg = require __DIR__ . '/../config/config.php';

$session = new SessionManager($cfg);
$session->start();

$auth = new AuthService($cfg);
$user = $auth->user();

if ($user) {
  if (!$auth->requireMfaVerified()) {
    header('Location: /axisbeauty/public/mfa.php');
    exit;
  }
  header('Location: /axisbeauty/public/dashboard.php');
  exit;
}

header('Location: /axisbeauty/public/login.php');
exit;
