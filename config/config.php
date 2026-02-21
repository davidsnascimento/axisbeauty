<?php
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'axisbeauty',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
  ],
  'billing' => [
    'gateway' => 'mercadopago',
    'mp_access_token' => 'SEU_ACCESS_TOKEN',
    'mp_webhook_secret' => 'SUA_WEBHOOK_SECRET',
    'mp_base_url' => 'https://api.mercadopago.com',
  ],
];
