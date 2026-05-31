-- ============================================================
-- GERENCIADOR DE EQUIPES — Módulo WhatsApp
-- Adições ao schema existente (gerenciador_equipes)
-- Execute após o schema.sql principal
-- ============================================================

USE gerenciador_equipes;

-- ------------------------------------------------------------
-- 1. TOKENS DE CONFIRMAÇÃO POR ESCALA
-- Cada escala enviada gera um token único.
-- O membro responde "CONFIRMAR" ou "NÃO POSSO" e o webhook
-- localiza a escala pelo número de telefone + token ativo.
-- ------------------------------------------------------------
CREATE TABLE escala_confirmacoes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  escala_id       INT NOT NULL,
  membro_id       INT,
  telefone        VARCHAR(20)  NOT NULL,          -- formato E.164: +5514999998888
  token           CHAR(64)     NOT NULL UNIQUE,   -- SHA-256 gerado no envio
  status          ENUM(
                    'pendente',
                    'enviado',
                    'confirmado',
                    'recusado',
                    'sem_resposta',
                    'erro_envio'
                  ) NOT NULL DEFAULT 'pendente',
  respondido_em   DATETIME,
  resposta_raw    VARCHAR(255),                   -- texto exato que o membro enviou
  tentativas      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ec_escala  FOREIGN KEY (escala_id) REFERENCES escalas(id) ON DELETE CASCADE,
  CONSTRAINT fk_ec_membro  FOREIGN KEY (membro_id) REFERENCES membros(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Índice para o webhook localizar rapidamente pelo telefone
CREATE INDEX idx_ec_telefone ON escala_confirmacoes(telefone);
-- Índice para reenvios/relatórios por status
CREATE INDEX idx_ec_status   ON escala_confirmacoes(status);

-- ------------------------------------------------------------
-- 2. LOG DE ENVIOS WHATSAPP
-- Registra cada tentativa de envio com o retorno da API Meta.
-- Útil para auditoria e reenvio em caso de falha.
-- ------------------------------------------------------------
CREATE TABLE whatsapp_envios (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  confirmacao_id  INT NOT NULL,
  message_id      VARCHAR(255),                  -- wamid retornado pela API Meta
  status_api      VARCHAR(50),                   -- sent / failed / delivered
  payload_enviado JSON,                          -- corpo da requisição (para debug)
  resposta_api    JSON,                          -- resposta completa da Meta
  erro            TEXT,                          -- mensagem de erro se houver
  criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_we_confirmacao FOREIGN KEY (confirmacao_id)
    REFERENCES escala_confirmacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. LOG DE WEBHOOKS RECEBIDOS
-- Grava cada evento que a Meta envia (mensagem recebida,
-- status de entrega, erros). Essencial para debug.
-- ------------------------------------------------------------
CREATE TABLE webhook_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tipo        VARCHAR(50),                       -- messages / statuses / errors
  de          VARCHAR(20),                       -- número do remetente E.164
  conteudo    TEXT,                              -- texto da mensagem ou JSON do evento
  payload_raw JSON,                              -- payload completo recebido
  processado  TINYINT(1) NOT NULL DEFAULT 0,     -- 0=não processado, 1=processado
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_wl_de        ON webhook_logs(de);
CREATE INDEX idx_wl_processado ON webhook_logs(processado);

-- ------------------------------------------------------------
-- 4. VIEW: situação de confirmações por escala
-- Facilita relatórios e o painel de status no frontend
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vw_confirmacoes_escala AS
SELECT
  e.id            AS escala_id,
  e.data_escala,
  e.horario,
  e.funcao,
  e.status        AS status_escala,
  min.nome        AS ministerio,
  mem.nome        AS membro_nome,
  mem.telefone    AS membro_telefone,
  ec.id           AS confirmacao_id,
  ec.status       AS status_confirmacao,
  ec.respondido_em,
  ec.resposta_raw,
  ec.tentativas,
  ec.token
FROM escalas e
LEFT JOIN ministerios min ON min.id = e.ministerio_id
LEFT JOIN membros     mem ON mem.id = e.membro_id
LEFT JOIN escala_confirmacoes ec ON ec.escala_id = e.id
ORDER BY e.data_escala, e.horario;

-- ------------------------------------------------------------
-- 5. VIEW: resumo de confirmações por ministério
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vw_resumo_confirmacoes AS
SELECT
  min.nome                                            AS ministerio,
  min.cor_hex,
  COUNT(ec.id)                                        AS total_enviados,
  SUM(ec.status = 'confirmado')                       AS confirmados,
  SUM(ec.status = 'recusado')                         AS recusados,
  SUM(ec.status = 'sem_resposta')                     AS sem_resposta,
  SUM(ec.status = 'erro_envio')                       AS erros,
  ROUND(
    100.0 * SUM(ec.status = 'confirmado') / NULLIF(COUNT(ec.id), 0), 1
  )                                                   AS taxa_confirmacao_pct
FROM ministerios min
LEFT JOIN escalas e   ON e.ministerio_id = min.id
LEFT JOIN escala_confirmacoes ec ON ec.escala_id = e.id
GROUP BY min.id, min.nome, min.cor_hex
ORDER BY min.nome;
