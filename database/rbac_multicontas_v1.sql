-- AXISBEAUTY RBAC + MULTI-CONTA (v1.0)
-- Compatível com MariaDB (XAMPP)

CREATE TABLE IF NOT EXISTS contas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  plano_codigo VARCHAR(40) NOT NULL DEFAULT 'FREE',
  status ENUM('ativa','suspensa','cancelada') NOT NULL DEFAULT 'ativa',
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- garantir conta_id na tabela usuarios (se já existir, não quebra)
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS conta_id INT NULL AFTER id;

-- index + FK (FK só se MariaDB permitir; se falhar, comente a linha FK)
ALTER TABLE usuarios
  ADD INDEX IF NOT EXISTS idx_usuarios_conta (conta_id);

-- Cria permissões padrão
INSERT INTO permissoes (chave, descricao) VALUES
('dashboard.ver','Acessar dashboard'),
('usuarios.gerenciar','Gerenciar usuários e cargos'),
('saloes.gerenciar','Gerenciar salões'),
('clientes.ver','Ver clientes'),
('clientes.criar','Criar clientes'),
('clientes.editar','Editar clientes'),
('clientes.excluir','Excluir clientes'),
('servicos.ver','Ver serviços'),
('servicos.criar','Criar serviços'),
('servicos.editar','Editar serviços'),
('servicos.excluir','Excluir serviços'),
('agenda.ver','Ver agenda'),
('agenda.criar','Criar agendamentos'),
('agenda.editar','Editar agendamentos'),
('agenda.cancelar','Cancelar agendamentos'),
('financeiro.ver','Ver financeiro'),
('financeiro.exportar','Exportar financeiro')
ON DUPLICATE KEY UPDATE chave=chave;

-- Mapeamento roles -> permissoes (padrão vendável)
-- super_admin: tudo (aqui damos um conjunto amplo; no app pode tratar super_admin como bypass)
INSERT IGNORE INTO role_permissoes (role_id, permissao_id)
SELECT r.id, p.id
FROM roles r
JOIN permissoes p
WHERE r.nome='super_admin';

-- dono_conta: quase tudo
INSERT IGNORE INTO role_permissoes (role_id, permissao_id)
SELECT r.id, p.id
FROM roles r
JOIN permissoes p
WHERE r.nome='dono_conta'
  AND p.chave IN (
    'dashboard.ver',
    'usuarios.gerenciar',
    'saloes.gerenciar',
    'clientes.ver','clientes.criar','clientes.editar','clientes.excluir',
    'servicos.ver','servicos.criar','servicos.editar','servicos.excluir',
    'agenda.ver','agenda.criar','agenda.editar','agenda.cancelar',
    'financeiro.ver','financeiro.exportar'
  );

-- gerente: operacional + agenda + clientes/serviços (sem export financeiro)
INSERT IGNORE INTO role_permissoes (role_id, permissao_id)
SELECT r.id, p.id
FROM roles r
JOIN permissoes p
WHERE r.nome='gerente'
  AND p.chave IN (
    'dashboard.ver',
    'clientes.ver','clientes.criar','clientes.editar',
    'servicos.ver','servicos.criar','servicos.editar',
    'agenda.ver','agenda.criar','agenda.editar','agenda.cancelar',
    'financeiro.ver'
  );

-- recepcao: agenda + clientes (sem excluir)
INSERT IGNORE INTO role_permissoes (role_id, permissao_id)
SELECT r.id, p.id
FROM roles r
JOIN permissoes p
WHERE r.nome='recepcao'
  AND p.chave IN (
    'dashboard.ver',
    'clientes.ver','clientes.criar','clientes.editar',
    'agenda.ver','agenda.criar','agenda.editar','agenda.cancelar'
  );

-- profissional: ver agenda (futuro: só a própria)
INSERT IGNORE INTO role_permissoes (role_id, permissao_id)
SELECT r.id, p.id
FROM roles r
JOIN permissoes p
WHERE r.nome='profissional'
  AND p.chave IN ('dashboard.ver','agenda.ver','clientes.ver');

-- financeiro: ver + export
INSERT IGNORE INTO role_permissoes (role_id, permissao_id)
SELECT r.id, p.id
FROM roles r
JOIN permissoes p
WHERE r.nome='financeiro'
  AND p.chave IN ('dashboard.ver','financeiro.ver','financeiro.exportar');

