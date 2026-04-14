-- ============================================================
-- Workday — Ferramentas por Quadro (Migration 003)
-- ============================================================

USE `workday`;

-- Adiciona coluna de ferramenta à tabela boards
ALTER TABLE `boards`
  ADD COLUMN IF NOT EXISTS `tool` ENUM('none','sipoc') NOT NULL DEFAULT 'none' AFTER `default_view`;

-- Tabela que armazena o conteúdo editável de cada ferramenta
CREATE TABLE IF NOT EXISTS `board_tool_data` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id`   INT UNSIGNED NOT NULL,
  `tool_type`  VARCHAR(50)  NOT NULL DEFAULT 'sipoc',
  `content`    LONGTEXT     NOT NULL COMMENT 'JSON com os dados da ferramenta',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_board_tool` (`board_id`, `tool_type`),
  FOREIGN KEY (`board_id`) REFERENCES `boards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
