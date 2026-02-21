<?php
// config/config.php

return [
  'db' => [
    'host' => 'localhost',
    'name' => 'axisbeauty',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],

  'security' => [
    // Troque por algo forte (32+ chars). NÃO suba em repositório público.
    'app_key' => 'TROQUE_ESSA_CHAVE_FORTE_AQUI_32+CHARS',
    'cookie_secure' => false, // true em HTTPS
    'cookie_samesite' => 'Lax',
  ],

  'auth' => [
    'session_name' => 'axisbeauty_sess',
    'session_ttl_minutes' => 120,
    'remember_me_days' => 30,
    'max_attempts_15min' => 5,
    'lock_minutes' => 5,
  ],

  'billing' => [
    'gateway' => 'mercadopago',
    'mp_access_token' => 'SEU_ACCESS_TOKEN',
    'mp_webhook_secret' => 'SUA_WEBHOOK_SECRET',
    'mp_base_url' => 'https://api.mercadopago.com',
  ],
];
