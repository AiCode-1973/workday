-- ============================================================
-- Workday - Schema do Banco de Dados
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `workday`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `workday`;

-- ============================================================
-- Usuários
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(100)  NOT NULL,
  `email`          VARCHAR(150)  NOT NULL UNIQUE,
  `password`       VARCHAR(255)  NOT NULL,
  `avatar`         VARCHAR(255)  DEFAULT NULL,
  `role`           ENUM('admin','member','viewer') NOT NULL DEFAULT 'member',
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `token`          VARCHAR(100)  DEFAULT NULL,
  `timezone`       VARCHAR(50)   DEFAULT 'America/Sao_Paulo',
  `last_login`     DATETIME      DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Times / Organizações
-- ============================================================
CREATE TABLE IF NOT EXISTS `workspaces` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150) NOT NULL,
  `slug`        VARCHAR(150) NOT NULL UNIQUE,
  `logo`        VARCHAR(255) DEFAULT NULL,
  `owner_id`    INT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `workspace_members` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `workspace_id` INT UNSIGNED NOT NULL,
  `user_id`      INT UNSIGNED NOT NULL,
  `role`         ENUM('admin','member','viewer') NOT NULL DEFAULT 'member',
  `joined_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_ws_member` (`workspace_id`, `user_id`),
  FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)      REFERENCES `users`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Portfólios / Pastas
-- ============================================================
CREATE TABLE IF NOT EXISTS `portfolios` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `workspace_id` INT UNSIGNED NOT NULL,
  `name`         VARCHAR(150) NOT NULL,
  `color`        VARCHAR(20)  DEFAULT '#6366f1',
  `icon`         VARCHAR(50)  DEFAULT 'folder',
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Quadros (Boards)
-- ============================================================
CREATE TABLE IF NOT EXISTS `boards` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `workspace_id` INT UNSIGNED NOT NULL,
  `portfolio_id` INT UNSIGNED DEFAULT NULL,
  `name`         VARCHAR(150) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `color`        VARCHAR(20)  DEFAULT '#6366f1',
  `icon`         VARCHAR(50)  DEFAULT 'layout',
  `default_view` ENUM('kanban','list','calendar','timeline','table','form') NOT NULL DEFAULT 'kanban',
  `is_private`   TINYINT(1) NOT NULL DEFAULT 0,
  `created_by`   INT UNSIGNED NOT NULL,
  `archived_at`  DATETIME DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_members` (
  `id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id` INT UNSIGNED NOT NULL,
  `user_id`  INT UNSIGNED NOT NULL,
  `role`     ENUM('admin','member','viewer') NOT NULL DEFAULT 'member',
  UNIQUE KEY `uq_board_member` (`board_id`, `user_id`),
  FOREIGN KEY (`board_id`) REFERENCES `boards`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Colunas / Status (Grupos do Kanban)
-- ============================================================
CREATE TABLE IF NOT EXISTS `board_groups` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id`   INT UNSIGNED NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `color`      VARCHAR(20)  DEFAULT '#94a3b8',
  `position`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_done`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`board_id`) REFERENCES `boards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Campos Personalizados
-- ============================================================
CREATE TABLE IF NOT EXISTS `board_fields` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id`     INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `type`         ENUM('text','number','date','datetime','select','multiselect','people','status','link','file','checkbox','rating','formula','progress') NOT NULL DEFAULT 'text',
  `options`      JSON DEFAULT NULL,  -- para select, status: [{"label":"Em Progresso","color":"#f59e0b"}]
  `position`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_required`  TINYINT(1) NOT NULL DEFAULT 0,
  `is_visible`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`board_id`) REFERENCES `boards`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Itens / Tarefas
-- ============================================================
CREATE TABLE IF NOT EXISTS `items` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id`      INT UNSIGNED NOT NULL,
  `group_id`      INT UNSIGNED NOT NULL,
  `parent_id`     INT UNSIGNED DEFAULT NULL,  -- subtarefas
  `title`         VARCHAR(500) NOT NULL,
  `description`   LONGTEXT    DEFAULT NULL,
  `position`      INT UNSIGNED NOT NULL DEFAULT 0,
  `priority`      ENUM('none','low','medium','high','urgent') NOT NULL DEFAULT 'none',
  `due_date`      DATE         DEFAULT NULL,
  `start_date`    DATE         DEFAULT NULL,
  `done_at`       DATETIME     DEFAULT NULL,
  `created_by`    INT UNSIGNED NOT NULL,
  `archived_at`   DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`board_id`)  REFERENCES `boards`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`group_id`)  REFERENCES `board_groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `items`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)  ON DELETE CASCADE,
  INDEX `idx_items_board`     (`board_id`),
  INDEX `idx_items_group`     (`group_id`),
  INDEX `idx_items_due_date`  (`due_date`),
  INDEX `idx_items_priority`  (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Valores dos Campos Personalizados por Item
-- ============================================================
CREATE TABLE IF NOT EXISTS `item_field_values` (
  `id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id`  INT UNSIGNED NOT NULL,
  `field_id` INT UNSIGNED NOT NULL,
  `value`    MEDIUMTEXT   DEFAULT NULL,
  UNIQUE KEY `uq_item_field` (`item_id`, `field_id`),
  FOREIGN KEY (`item_id`)  REFERENCES `items`(`id`)        ON DELETE CASCADE,
  FOREIGN KEY (`field_id`) REFERENCES `board_fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Responsáveis por Item
-- ============================================================
CREATE TABLE IF NOT EXISTS `item_assignees` (
  `item_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `user_id`),
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Etiquetas / Labels
-- ============================================================
CREATE TABLE IF NOT EXISTS `labels` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `workspace_id` INT UNSIGNED NOT NULL,
  `name`         VARCHAR(80) NOT NULL,
  `color`        VARCHAR(20) NOT NULL DEFAULT '#6366f1',
  FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `item_labels` (
  `item_id`  INT UNSIGNED NOT NULL,
  `label_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`item_id`, `label_id`),
  FOREIGN KEY (`item_id`)  REFERENCES `items`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`label_id`) REFERENCES `labels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Dependências entre Itens
-- ============================================================
CREATE TABLE IF NOT EXISTS `item_dependencies` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id`         INT UNSIGNED NOT NULL,
  `depends_on_id`   INT UNSIGNED NOT NULL,
  `type`            ENUM('blocks','is_blocked_by','relates_to') NOT NULL DEFAULT 'blocks',
  UNIQUE KEY `uq_dep` (`item_id`, `depends_on_id`),
  FOREIGN KEY (`item_id`)       REFERENCES `items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`depends_on_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Comentários
-- ============================================================
CREATE TABLE IF NOT EXISTS `comments` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id`    INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `parent_id`  INT UNSIGNED DEFAULT NULL, -- threads
  `body`       TEXT NOT NULL,
  `edited_at`  DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`)   REFERENCES `items`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
  INDEX `idx_comments_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Anexos / Arquivos
-- ============================================================
CREATE TABLE IF NOT EXISTS `attachments` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id`     INT UNSIGNED NOT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `filename`    VARCHAR(255) NOT NULL,
  `original`    VARCHAR(255) NOT NULL,
  `mime_type`   VARCHAR(100) NOT NULL,
  `size`        INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`)     REFERENCES `items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Log de Atividades
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id`   INT UNSIGNED DEFAULT NULL,
  `item_id`    INT UNSIGNED DEFAULT NULL,
  `user_id`    INT UNSIGNED NOT NULL,
  `action`     VARCHAR(100) NOT NULL,  -- 'item.created', 'item.moved', 'comment.added', etc.
  `meta`       JSON         DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`board_id`) REFERENCES `boards`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`)  REFERENCES `items`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  INDEX `idx_log_board` (`board_id`),
  INDEX `idx_log_item`  (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Notificações
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       VARCHAR(80)  NOT NULL,
  `title`      VARCHAR(255) NOT NULL,
  `body`       TEXT         DEFAULT NULL,
  `link`       VARCHAR(500) DEFAULT NULL,
  `read_at`    DATETIME     DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`, `read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Automações / Workflows
-- ============================================================
CREATE TABLE IF NOT EXISTS `automations` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `board_id`    INT UNSIGNED NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `trigger`     JSON NOT NULL,  -- {"event": "status_changed", "from": "...", "to": "..."}
  `conditions`  JSON DEFAULT NULL,
  `actions`     JSON NOT NULL,  -- [{"type": "notify_user", "user_id": 1}, ...]
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`board_id`)  REFERENCES `boards`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Webhooks
-- ============================================================
CREATE TABLE IF NOT EXISTS `webhooks` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `workspace_id` INT UNSIGNED NOT NULL,
  `url`          VARCHAR(500) NOT NULL,
  `events`       JSON         NOT NULL,  -- ["item.created", "item.updated"]
  `secret`       VARCHAR(100) DEFAULT NULL,
  `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`)   REFERENCES `users`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tokens de API
-- ============================================================
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `token`        VARCHAR(255) NOT NULL UNIQUE,
  `last_used_at` DATETIME     DEFAULT NULL,
  `expires_at`   DATETIME     DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Sessions
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id`         VARCHAR(128) PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `ip`         VARCHAR(45)  DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
