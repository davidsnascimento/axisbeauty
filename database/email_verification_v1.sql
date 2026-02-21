-- EMAIL VERIFICATION (v1.0)
-- Rode após auth_hardcore_v1.sql e rbac_multicontas_v1.sql

CREATE TABLE IF NOT EXISTS email_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  usado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (usuario_id),
  CONSTRAINT fk_ev_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- garantir colunas em usuarios
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS email_verificado_em DATETIME NULL AFTER status;

-- opcional: status pendente já existe no ENUM, então ok.
