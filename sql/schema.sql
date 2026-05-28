-- ============================================================
-- GERENCIADOR DE EQUIPES — Igreja Verbo da Vida, Bauru
-- MySQL / MariaDB — v3 final consolidada
-- LBD 3 · FATEC Bauru
-- ============================================================

CREATE DATABASE IF NOT EXISTS gerenciador_equipes
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gerenciador_equipes;

-- ============================================================
-- TABELAS
-- ============================================================

CREATE TABLE ministerios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(100) NOT NULL,
  descricao     TEXT,
  cor_hex       VARCHAR(7)  NOT NULL DEFAULT '#0D9488',
  icone         VARCHAR(10)          DEFAULT '⛪',
  ativo         TINYINT(1)  NOT NULL DEFAULT 1,
  criado_em     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE habilidades (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE disponibilidades (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  descricao VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE supervisores (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(150) NOT NULL,
  email           VARCHAR(200) NOT NULL UNIQUE,
  telefone        VARCHAR(20),
  ministerio_id   INT,
  status          ENUM('ativo','pendente','inativo') NOT NULL DEFAULT 'pendente',
  avatar_iniciais CHAR(3),
  avatar_cor      VARCHAR(7) DEFAULT '#0D9488',
  criado_em       DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em   DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sup_ministerio
    FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- membros.supervisor_id ligado a supervisores para relatório individual
CREATE TABLE membros (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(150) NOT NULL,
  email           VARCHAR(200) UNIQUE,
  telefone        VARCHAR(20),
  ministerio_id   INT,
  supervisor_id   INT,
  funcao          VARCHAR(100),
  status          ENUM('ativo','inativo','pendente') NOT NULL DEFAULT 'ativo',
  avatar_iniciais CHAR(3),
  avatar_cor      VARCHAR(7) DEFAULT '#0D9488',
  criado_em       DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em   DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mem_ministerio
    FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE SET NULL,
  CONSTRAINT fk_mem_supervisor
    FOREIGN KEY (supervisor_id) REFERENCES supervisores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE membro_habilidades (
  membro_id     INT NOT NULL,
  habilidade_id INT NOT NULL,
  PRIMARY KEY (membro_id, habilidade_id),
  FOREIGN KEY (membro_id)     REFERENCES membros(id)     ON DELETE CASCADE,
  FOREIGN KEY (habilidade_id) REFERENCES habilidades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE membro_disponibilidades (
  membro_id          INT NOT NULL,
  disponibilidade_id INT NOT NULL,
  PRIMARY KEY (membro_id, disponibilidade_id),
  FOREIGN KEY (membro_id)          REFERENCES membros(id)          ON DELETE CASCADE,
  FOREIGN KEY (disponibilidade_id) REFERENCES disponibilidades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- eventos.ministerio_id = ministério "principal" (cor no calendário)
CREATE TABLE eventos (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  titulo        VARCHAR(150) NOT NULL,
  descricao     TEXT,
  ministerio_id INT,
  data_evento   DATE NOT NULL,
  horario       TIME NOT NULL,
  tipo          ENUM('louvor','culto','kids','reuniao','escala_especial','cultural','outro')
                NOT NULL DEFAULT 'culto',
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_evt_ministerio
    FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- N:N eventos ↔ ministérios — múltiplos ministérios por evento
CREATE TABLE evento_ministerios (
  evento_id     INT NOT NULL,
  ministerio_id INT NOT NULL,
  PRIMARY KEY (evento_id, ministerio_id),
  FOREIGN KEY (evento_id)     REFERENCES eventos(id)     ON DELETE CASCADE,
  FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- escalas.membro_id liga diretamente ao membro — base dos relatórios de presença
CREATE TABLE escalas (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  evento_id     INT,
  ministerio_id INT,
  supervisor_id INT,
  membro_id     INT,
  data_escala   DATE         NOT NULL,
  horario       TIME         NOT NULL,
  funcao        VARCHAR(100),
  status        ENUM('confirmado','pendente','ausente') NOT NULL DEFAULT 'pendente',
  observacoes   TEXT,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_esc_evento     FOREIGN KEY (evento_id)     REFERENCES eventos(id)     ON DELETE SET NULL,
  CONSTRAINT fk_esc_ministerio FOREIGN KEY (ministerio_id) REFERENCES ministerios(id) ON DELETE SET NULL,
  CONSTRAINT fk_esc_supervisor FOREIGN KEY (supervisor_id) REFERENCES supervisores(id) ON DELETE SET NULL,
  CONSTRAINT fk_esc_membro     FOREIGN KEY (membro_id)     REFERENCES membros(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notificacoes (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  titulo    VARCHAR(200) NOT NULL,
  mensagem  TEXT,
  tipo      ENUM('info','alerta','sucesso','erro') DEFAULT 'info',
  lida      TINYINT(1) NOT NULL DEFAULT 0,
  criado_em DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- VIEWS
-- ============================================================

-- Hierarquia completa: supervisor → ministério → membros
CREATE OR REPLACE VIEW vw_hierarquia_supervisores AS
SELECT
  s.id              AS supervisor_id,
  s.nome            AS supervisor_nome,
  s.email           AS supervisor_email,
  s.telefone        AS supervisor_telefone,
  s.status          AS supervisor_status,
  s.avatar_iniciais AS supervisor_iniciais,
  s.avatar_cor      AS supervisor_cor,
  m.id              AS ministerio_id,
  m.nome            AS ministerio_nome,
  m.cor_hex         AS ministerio_cor,
  m.icone           AS ministerio_icone,
  mem.id            AS membro_id,
  mem.nome          AS membro_nome,
  mem.funcao        AS membro_funcao,
  mem.status        AS membro_status,
  mem.avatar_iniciais AS membro_iniciais,
  mem.avatar_cor    AS membro_cor
FROM supervisores s
LEFT JOIN ministerios m   ON m.id  = s.ministerio_id
LEFT JOIN membros     mem ON mem.ministerio_id = s.ministerio_id
ORDER BY s.nome, mem.nome;

-- Resumo por supervisor com total de membros
CREATE OR REPLACE VIEW vw_resumo_supervisores AS
SELECT
  s.id, s.nome, s.email, s.telefone, s.status,
  s.avatar_iniciais, s.avatar_cor,
  m.nome    AS ministerio,
  m.cor_hex AS ministerio_cor,
  m.icone   AS ministerio_icone,
  COUNT(DISTINCT mem.id) AS total_membros
FROM supervisores s
LEFT JOIN ministerios m   ON m.id = s.ministerio_id
LEFT JOIN membros     mem ON mem.ministerio_id = s.ministerio_id
GROUP BY s.id, s.nome, s.email, s.telefone, s.status,
         s.avatar_iniciais, s.avatar_cor,
         m.nome, m.cor_hex, m.icone;

-- Eventos com todos os ministérios participantes
CREATE OR REPLACE VIEW vw_eventos_ministerios AS
SELECT
  e.id, e.titulo, e.data_evento, e.horario, e.tipo, e.descricao,
  mp.nome    AS ministerio_principal,
  mp.cor_hex AS cor_principal,
  GROUP_CONCAT(DISTINCT mi.nome  ORDER BY mi.nome SEPARATOR ', ') AS ministerios_participantes,
  GROUP_CONCAT(DISTINCT em.ministerio_id ORDER BY em.ministerio_id) AS ministerios_ids
FROM eventos e
LEFT JOIN ministerios mp ON mp.id = e.ministerio_id
LEFT JOIN evento_ministerios em ON em.evento_id = e.id
LEFT JOIN ministerios mi ON mi.id = em.ministerio_id
GROUP BY e.id, e.titulo, e.data_evento, e.horario,
         e.tipo, e.descricao, mp.nome, mp.cor_hex
ORDER BY e.data_evento, e.horario;

-- Presença por ministério (base dos relatórios)
-- Usa ministerio_id (int) — mesma lógica do frontend
CREATE OR REPLACE VIEW vw_presenca_por_ministerio AS
SELECT
  min.id      AS ministerio_id,
  min.nome    AS ministerio,
  min.cor_hex,
  min.icone,
  COUNT(e.id)                  AS total_escalas,
  SUM(e.status = 'confirmado') AS confirmados,
  SUM(e.status = 'pendente')   AS pendentes,
  SUM(e.status = 'ausente')    AS ausentes,
  ROUND(
    100.0 * SUM(e.status = 'confirmado') / NULLIF(COUNT(e.id), 0), 1
  )                            AS taxa_presenca_pct
FROM ministerios min
LEFT JOIN escalas e ON e.ministerio_id = min.id
GROUP BY min.id, min.nome, min.cor_hex, min.icone
ORDER BY min.nome;

-- Presença por membro (base do relatório "Por Membro")
-- Usa membro_id (int) — join direto, sem depender de string de nome
CREATE OR REPLACE VIEW vw_presenca_por_membro AS
SELECT
  mem.id              AS membro_id,
  mem.nome            AS membro,
  mem.funcao,
  mem.avatar_iniciais,
  mem.avatar_cor,
  min.id              AS ministerio_id,
  min.nome            AS ministerio,
  min.icone           AS ministerio_icone,
  COUNT(e.id)                  AS total_escalas,
  SUM(e.status = 'confirmado') AS confirmados,
  SUM(e.status = 'pendente')   AS pendentes,
  SUM(e.status = 'ausente')    AS ausentes,
  ROUND(
    100.0 * SUM(e.status = 'confirmado') / NULLIF(COUNT(e.id), 0), 1
  )                            AS taxa_presenca_pct
FROM membros mem
LEFT JOIN ministerios min ON min.id = mem.ministerio_id
LEFT JOIN escalas e       ON e.membro_id = mem.id
GROUP BY mem.id, mem.nome, mem.funcao,
         mem.avatar_iniciais, mem.avatar_cor,
         min.id, min.nome, min.icone
ORDER BY mem.nome;

-- KPIs globais de presença
CREATE OR REPLACE VIEW vw_presenca_global AS
SELECT
  COUNT(*)                     AS total_escalas,
  SUM(status = 'confirmado')   AS confirmados,
  SUM(status = 'pendente')     AS pendentes,
  SUM(status = 'ausente')      AS ausentes,
  ROUND(
    100.0 * SUM(status = 'confirmado') / NULLIF(COUNT(*), 0), 1
  )                            AS taxa_presenca_pct
FROM escalas;

-- Resumo por equipe (para relatório "Por Equipe")
CREATE OR REPLACE VIEW vw_resumo_por_equipe AS
SELECT
  min.id      AS ministerio_id,
  min.nome    AS ministerio,
  min.cor_hex,
  min.icone,
  COUNT(DISTINCT s.id)   AS total_supervisores,
  COUNT(DISTINCT mem.id) AS total_membros,
  COUNT(DISTINCT e.id)   AS total_escalas,
  SUM(e.status = 'confirmado') AS confirmados,
  ROUND(
    100.0 * SUM(e.status = 'confirmado') / NULLIF(COUNT(e.id), 0), 1
  )                            AS taxa_presenca_pct
FROM ministerios min
LEFT JOIN supervisores s   ON s.ministerio_id  = min.id
LEFT JOIN membros      mem ON mem.ministerio_id = min.id
LEFT JOIN escalas      e   ON e.ministerio_id  = min.id
GROUP BY min.id, min.nome, min.cor_hex, min.icone
ORDER BY min.nome;

-- ============================================================
-- DADOS INICIAIS (seed)
-- ============================================================

INSERT INTO ministerios (nome, descricao, cor_hex, icone) VALUES
  ('Louvor',   'Ministério de louvor e adoração',    '#0D9488', '🎵'),
  ('Mídia',    'Equipe de mídia, câmera e som',      '#F97316', '📷'),
  ('Kids',     'Ministério infantil',                '#EAB308', '🧒'),
  ('Recepção', 'Equipe de recepção e acolhimento',   '#16A34A', '🤝'),
  ('Oração',   'Grupo de intercessão e oração',      '#7C3AED', '🙏');

INSERT INTO habilidades (nome) VALUES
  ('Violão'),('Teclado'),('Bateria'),('Baixo'),('Vocal'),
  ('Câmera'),('Som'),('Designer'),('Transmissão'),
  ('Professora'),('Auxiliar'),('Recepcionista');

INSERT INTO disponibilidades (descricao) VALUES
  ('Dom. Manhã'),('Dom. Tarde'),('Dom. Noite'),
  ('Qua. Noite'),('Sex. Noite'),('Sáb. Tarde');

INSERT INTO supervisores (nome, email, telefone, ministerio_id, status, avatar_iniciais, avatar_cor) VALUES
  ('Ana Souza',     'ana.souza@email.com',   '(14) 99123-4567', 1, 'ativo',    'AS', '#0D9488'),
  ('Carlos Lima',   'carlos.lima@email.com', '(14) 98765-4321', 1, 'ativo',    'CL', '#F97316'),
  ('Maria Oliveira','maria.o@email.com',     '(14) 97654-3210', 2, 'pendente', 'MO', '#7C3AED'),
  ('Paulo Ferreira','paulo.f@email.com',     '(14) 96543-2109', 2, 'ativo',    'PF', '#16A34A'),
  ('Lúcia Mendes',  'lucia.m@email.com',     '(14) 95432-1098', 3, 'pendente', 'LM', '#EAB308'),
  ('Marcos Dias',   'marcos.d@email.com',    '(14) 94321-0987', 4, 'ativo',    'MD', '#EC4899');

-- supervisor_id preenchido — relatório por membro funciona
INSERT INTO membros (nome, email, ministerio_id, supervisor_id, funcao, avatar_iniciais, avatar_cor) VALUES
  ('Ana Beatriz Costa',    'ana.b@email.com',      1, 1, 'Violão',        'A', '#0D9488'),
  ('Carlos Eduardo Melo',  'c.melo@email.com',     2, 4, 'Câmera',        'C', '#F97316'),
  ('Fernanda Lima Rocha',  'fernanda.r@email.com', 3, 5, 'Professora',    'F', '#7C3AED'),
  ('Roberto Santos Jr.',   'roberto.s@email.com',  4, 6, 'Recepcionista', 'R', '#16A34A'),
  ('Juliana Pereira Silva','juliana.s@email.com',  1, 1, 'Vocal',         'J', '#EAB308'),
  ('Marcos Rodrigues',     'marcos.r@email.com',   2, 4, 'Designer',      'M', '#EC4899');

INSERT INTO eventos (titulo, data_evento, horario, tipo) VALUES
  ('Culto de Louvor',   '2025-04-06', '09:00:00', 'louvor'),
  ('Culto de Quarta',   '2025-04-09', '19:30:00', 'culto'),
  ('Escala Especial',   '2025-04-13', '09:00:00', 'escala_especial'),
  ('Ministério Kids',   '2025-04-13', '09:00:00', 'kids'),
  ('Culto de Louvor',   '2025-04-20', '09:00:00', 'louvor'),
  ('Culto de Quarta',   '2025-04-23', '19:30:00', 'culto'),
  ('Reunião de Equipe', '2025-04-27', '19:00:00', 'reuniao'),
  ('Culto de Louvor',   '2025-05-04', '09:00:00', 'louvor'),
  ('Ministério Kids',   '2025-05-11', '09:00:00', 'kids'),
  ('Culto de Quarta',   '2025-05-14', '19:30:00', 'culto');

INSERT INTO evento_ministerios (evento_id, ministerio_id) VALUES
  (1,1),(1,2),
  (2,1),(2,2),(2,4),
  (3,1),(3,2),(3,3),(3,4),
  (4,3),
  (5,1),(5,2),
  (6,1),(6,2),
  (7,1),(7,2),(7,3),(7,4),(7,5),
  (8,1),(8,2),
  (9,3),
  (10,1),(10,2),(10,4);

-- escalas com ministerio_id (int) e membro_id (int)
-- base de todos os relatórios de presença
INSERT INTO escalas (ministerio_id, supervisor_id, membro_id, data_escala, horario, funcao, status, observacoes) VALUES
  (1, 1, 1, '2025-04-06', '09:00:00', 'Violão',        'confirmado', ''),
  (1, 2, 5, '2025-04-06', '19:30:00', 'Teclado',       'confirmado', ''),
  (2, 4, 2, '2025-04-09', '19:30:00', 'Câmera 1',      'pendente',   ''),
  (3, 5, 3, '2025-04-13', '09:00:00', 'Professora',    'confirmado', ''),
  (4, 6, 4, '2025-04-13', '08:30:00', 'Recepção',      'pendente',   ''),
  (2, 4, 2, '2025-04-16', '19:30:00', 'Som',           'ausente',    'Imprevisto'),
  (1, 1, 5, '2025-04-20', '09:00:00', 'Vocal',         'confirmado', ''),
  (3, 5, 3, '2025-04-20', '09:00:00', 'Auxiliar',      'pendente',   ''),
  (1, 2, 1, '2025-04-23', '09:00:00', 'Violão',        'ausente',    'Viagem'),
  (2, 4, 6, '2025-04-23', '19:30:00', 'Designer',      'confirmado', '');

INSERT INTO notificacoes (titulo, mensagem, tipo) VALUES
  ('Escala pendente',  '3 escalas aguardam confirmação esta semana.',          'alerta'),
  ('Novo membro',      'Marcos Rodrigues foi cadastrado no ministério Mídia.', 'sucesso'),
  ('Falta registrada', 'Carlos Eduardo marcou ausência no culto de quarta.',   'alerta');
