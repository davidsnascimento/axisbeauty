<?php
// src/Security/Validator.php

class Validator {
  public static function email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  public static function strongPassword(string $pass): array {
    $errors = [];
    if (strlen($pass) < 10) $errors[] = 'Senha deve ter pelo menos 10 caracteres.';
    if (!preg_match('/[A-Z]/', $pass)) $errors[] = 'Senha precisa de letra maiúscula.';
    if (!preg_match('/[a-z]/', $pass)) $errors[] = 'Senha precisa de letra minúscula.';
    if (!preg_match('/\d/', $pass)) $errors[] = 'Senha precisa de número.';
    if (!preg_match('/[^A-Za-z0-9]/', $pass)) $errors[] = 'Senha precisa de símbolo.';
    return $errors;
  }
}
