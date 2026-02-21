<?php
// src/Plans/LimitService.php

require_once __DIR__ . '/../Database.php';

class LimitService {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::pdo();
  }

  public function getLimit(int $planoId, string $chave): int {
    $stmt = $this->pdo->prepare("
      SELECT limite FROM limites_planos
      WHERE plano_id=:pid AND chave=:chave AND ativo=1
      LIMIT 1
    ");
    $stmt->execute([':pid'=>$planoId, ':chave'=>$chave]);
    $row = $stmt->fetch();
    return $row ? (int)$row['limite'] : -1;
  }

  public function canCreateByTotal(int $planoId, string $chave, int $totalAtual): bool {
    $lim = $this->getLimit($planoId, $chave);
    if ($lim < 0) return true;
    return ($totalAtual + 1) <= $lim;
  }

  public function canConsumeMonthly(int $contaId, int $planoId, string $chave, int $quantidade = 1): bool {
    $lim = $this->getLimit($planoId, $chave);
    if ($lim < 0) return true;

    [$ano, $mes] = $this->currentYearMonth();
    $usado = $this->getMonthlyUsage($contaId, $ano, $mes, $chave);

    return ($usado + $quantidade) <= $lim;
  }

  public function consumeMonthly(int $contaId, string $chave, int $quantidade = 1): void {
    [$ano, $mes] = $this->currentYearMonth();

    $stmt = $this->pdo->prepare("
      INSERT INTO uso_mensal (conta_id, ano, mes, chave, usado)
      VALUES (:cid, :ano, :mes, :chave, :usado)
      ON DUPLICATE KEY UPDATE usado = usado + VALUES(usado), atualizado_em=NOW()
    ");
    $stmt->execute([
      ':cid' => $contaId,
      ':ano' => $ano,
      ':mes' => $mes,
      ':chave' => $chave,
      ':usado' => $quantidade
    ]);
  }

  public function getMonthlyUsage(int $contaId, int $ano, int $mes, string $chave): int {
    $stmt = $this->pdo->prepare("
      SELECT usado
      FROM uso_mensal
      WHERE conta_id=:cid AND ano=:ano AND mes=:mes AND chave=:chave
      LIMIT 1
    ");
    $stmt->execute([
      ':cid' => $contaId,
      ':ano' => $ano,
      ':mes' => $mes,
      ':chave' => $chave
    ]);
    $row = $stmt->fetch();
    return $row ? (int)$row['usado'] : 0;
  }

  private function currentYearMonth(): array {
    return [(int)date('Y'), (int)date('n')];
  }
}
