<?php
require_once __DIR__ . '/../Database.php';

class LimitService {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::pdo();
  }

  public function canCreateByTotal(int $planoId, string $chave, int $totalAtual): bool {
    $stmt = $this->pdo->prepare("SELECT limite FROM limites_planos WHERE plano_id=:p AND chave=:c LIMIT 1");
    $stmt->execute([':p'=>$planoId, ':c'=>$chave]);
    $row = $stmt->fetch();
    if (!$row) return True;
    $lim = (int)$row['limite'];
    if ($lim < 0) return True;
    return ($totalAtual + 1) <= $lim;
  }
}
