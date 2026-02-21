<?php
// src/Auth/ContaRepository.php
//
// Repositório de contas (tenant).
// No cadastro, criamos uma conta e associamos o primeiro usuário como dono_conta.

require_once __DIR__ . '/../Database.php';

class ContaRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::pdo();
  }

  public function create(string $nome, string $planoCodigo='FREE'): int {
    $stmt = $this->pdo->prepare("
      INSERT INTO contas (nome, plano_codigo, status)
      VALUES (:n, :p, 'ativa')
    ");
    $stmt->execute([':n'=>$nome, ':p'=>$planoCodigo]);
    return (int)$this->pdo->lastInsertId();
  }
}
