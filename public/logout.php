<?php
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Auth/SessionManager.php';
require_once __DIR__ . '/../src/Security/SecurityHeaders.php';

SecurityHeaders::apply();
$cfg = require __DIR__ . '/../config/config.php';

$session = new SessionManager($cfg);
$session->start();

$auth = new AuthService($cfg);
$auth->logout();

header('Location: /axisbeauty/public/login.php');
exit;
