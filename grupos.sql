-- 1. LIMPEZA (separe cada DROP)
DROP VIEW IF EXISTS vw_pontos_itens_user;
DROP VIEW IF EXISTS vw_pontos_itens_grupo;
DROP VIEW IF EXISTS vw_pontos_mensalidade_user;
DROP VIEW IF EXISTS vw_pontos_mensalidade_grupo;
DROP VIEW IF EXISTS vw_grupo_em_dia;
DROP PROCEDURE IF EXISTS atualizar_pontuacoes_user;
DROP PROCEDURE IF EXISTS atualizar_pontuacoes_grupo;
DROP TRIGGER IF EXISTS trg_items_pedido_insert;
DROP TRIGGER IF EXISTS trg_items_pedido_update;
DROP TRIGGER IF EXISTS trg_mensalidade_update;
DROP TABLE IF EXISTS pontuacoes_grupos;
DROP TABLE IF EXISTS pontuacoes_clientes;

-- 2. Tabelas (STORED corrigido)
CREATE TABLE pontuacoes_clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  mes_ano VARCHAR(7) NOT NULL,
  pontos_mensalidade INT DEFAULT 0,
  pontos_itens INT DEFAULT 0,
  pontos_desafios INT DEFAULT 0,
  pontos_bonus_grupo INT DEFAULT 0,
  total INT GENERATED ALWAYS AS (pontos_mensalidade + pontos_itens + pontos_desafios + pontos_bonus_grupo) STORED,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  UNIQUE KEY unique_user_mes (user_id, mes_ano)
);

CREATE TABLE pontuacoes_grupos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  mes_ano VARCHAR(7) NOT NULL,
  pontos_mensalidades INT DEFAULT 0,
  pontos_itens DECIMAL(10,1) DEFAULT 0,
  total DECIMAL(10,1) GENERATED ALWAYS AS (pontos_mensalidades + pontos_itens) STORED,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id),
  UNIQUE KEY unique_grupo_mes (grupo_id, mes_ano)
);

-- 3. Views
CREATE OR REPLACE VIEW vw_pontos_itens_user AS
SELECT p.user_id, CONCAT(YEAR(p.data_pedido), '-', LPAD(MONTH(p.data_pedido), 2, '0')) AS mes_ano, COUNT(ip.id) AS pontos_itens
FROM items_pedido ip JOIN pedidos p ON ip.pedido_id = p.id WHERE ip.status_item = 'ativo' GROUP BY p.user_id, mes_ano;

CREATE OR REPLACE VIEW vw_pontos_itens_grupo AS
SELECT gm.grupo_id, CONCAT(YEAR(p.data_pedido), '-', LPAD(MONTH(p.data_pedido), 2, '0')) AS mes_ano, COUNT(ip.id) * 0.5 AS pontos_itens
FROM grupo_membros gm JOIN pedidos p ON gm.user_id = p.user_id JOIN items_pedido ip ON p.id = ip.pedido_id WHERE ip.status_item = 'ativo' GROUP BY gm.grupo_id, mes_ano;

CREATE OR REPLACE VIEW vw_pontos_mensalidade_user AS
SELECT user_id, CONCAT(competencia_ano, '-', LPAD(competencia_mes, 2, '0')) AS mes_ano, CASE WHEN status_pagamento = 'pago' AND DAY(pago_em) <= 25 THEN 100 ELSE 0 END AS pontos_mensalidade_em_dia FROM clube_mensalidades;

CREATE OR REPLACE VIEW vw_pontos_mensalidade_grupo AS
SELECT gm.grupo_id, CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0')) AS mes_ano, SUM(CASE WHEN cm.status_pagamento = 'pago' AND DAY(cm.pago_em) <= 25 THEN 100 ELSE 0 END) * 2 AS pontos_mensalidades FROM grupo_membros gm JOIN clube_mensalidades cm ON gm.user_id = cm.user_id GROUP BY gm.grupo_id, mes_ano;

CREATE OR REPLACE VIEW vw_grupo_em_dia AS
SELECT gm.grupo_id, CONCAT(cm.competencia_ano, '-', LPAD(cm.competencia_mes, 2, '0')) AS mes_ano, COUNT(CASE WHEN cm.status_pagamento = 'pago' AND DAY(cm.pago_em) <= 25 THEN 1 END) AS membros_em_dia, COUNT(gm.user_id) AS total_membros, CASE WHEN COUNT(CASE WHEN cm.status_pagamento = 'pago' AND DAY(cm.pago_em) <= 25 THEN 1 END) = COUNT(gm.user_id) THEN 1 ELSE 0 END AS grupo_100_em_dia FROM grupo_membros gm LEFT JOIN clube_mensalidades cm ON gm.user_id = cm.user_id GROUP BY gm.grupo_id, mes_ano;

-- 4. Procedures
DELIMITER //
CREATE PROCEDURE atualizar_pontuacoes_user(IN p_user_id BIGINT UNSIGNED, IN p_mes_ano VARCHAR(7))
BEGIN
  DECLARE v_mensal INT DEFAULT 0;
  DECLARE v_itens INT DEFAULT 0;
  DECLARE v_bonus INT DEFAULT 0;
  DECLARE v_grupo_id INT DEFAULT NULL;
  SELECT COALESCE(SUM(pontos_mensalidade_em_dia), 0) INTO v_mensal FROM vw_pontos_mensalidade_user WHERE user_id = p_user_id AND mes_ano = p_mes_ano;
  SELECT COALESCE(SUM(pontos_itens