-- Dados de demo para workday
-- Execute após 001_schema.sql

USE `workday`;

-- Todos os usuários demo têm senha: password123
INSERT INTO `users` (`name`, `email`, `password`, `role`, `is_active`, `email_verified`) VALUES
('Administrador', 'admin@workday.app', '$2y$12$kR8eaGvFKffSIRz7o8XYpO5lf2tpIgUtdgkWwgcBLIzZaE54s5n1a', 'admin', 1, 1),
('João Silva',    'joao@workday.app',  '$2y$12$kR8eaGvFKffSIRz7o8XYpO5lf2tpIgUtdgkWwgcBLIzZaE54s5n1a', 'member', 1, 1),
('Ana Costa',     'ana@workday.app',   '$2y$12$kR8eaGvFKffSIRz7o8XYpO5lf2tpIgUtdgkWwgcBLIzZaE54s5n1a', 'member', 1, 1);

-- Workspace demo
INSERT INTO `workspaces` (`name`, `slug`, `owner_id`) VALUES ('Minha Empresa', 'minha-empresa', 1);

-- Membros no workspace
INSERT INTO `workspace_members` (`workspace_id`, `user_id`, `role`) VALUES
(1, 1, 'admin'), (1, 2, 'member'), (1, 3, 'member');

-- Portfolio demo
INSERT INTO `portfolios` (`workspace_id`, `name`, `color`, `created_by`) VALUES
(1, 'Desenvolvimento', '#6366f1', 1),
(1, 'Marketing', '#f59e0b', 1);

-- Board demo
INSERT INTO `boards` (`workspace_id`, `portfolio_id`, `name`, `description`, `color`, `default_view`, `created_by`) VALUES
(1, 1, 'Website Corporativo', 'Redesign completo do site', '#6366f1', 'kanban', 1),
(1, 2, 'Campanha Q1 2025',    'Campanha de início de ano',  '#f59e0b', 'kanban', 1);

-- Grupos (colunas kanban) do board 1
INSERT INTO `board_groups` (`board_id`, `name`, `color`, `position`, `is_done`) VALUES
(1, 'Backlog',      '#94a3b8', 0, 0),
(1, 'Em Progresso', '#3b82f6', 1, 0),
(1, 'Em Revisão',   '#f59e0b', 2, 0),
(1, 'Concluído',    '#22c55e', 3, 1);

-- Grupos do board 2
INSERT INTO `board_groups` (`board_id`, `name`, `color`, `position`, `is_done`) VALUES
(2, 'A Fazer',  '#94a3b8', 0, 0),
(2, 'Fazendo',  '#3b82f6', 1, 0),
(2, 'Feito',    '#22c55e', 2, 1);

-- Campos personalizados do board 1
INSERT INTO `board_fields` (`board_id`, `name`, `type`, `options`, `position`) VALUES
(1, 'Prioridade',  'select',   '[{"label":"Baixa","color":"#22c55e"},{"label":"Média","color":"#f59e0b"},{"label":"Alta","color":"#ef4444"}]', 0),
(1, 'Estimativa',  'number',   NULL, 1),
(1, 'Progresso',   'progress', NULL, 2),
(1, 'URL Staging', 'link',     NULL, 3);

-- Itens no board 1
INSERT INTO `items` (`board_id`, `group_id`, `title`, `priority`, `due_date`, `created_by`, `position`) VALUES
(1, 1, 'Definir paleta de cores',         'high',   '2025-02-10', 1, 0),
(1, 1, 'Wireframes mobile',               'medium', '2025-02-15', 2, 1),
(1, 2, 'Desenvolver header responsivo',   'high',   '2025-02-20', 1, 0),
(1, 2, 'Integrar CMS',                    'medium', '2025-02-25', 3, 1),
(1, 3, 'Review da página inicial',        'high',   '2025-03-01', 2, 0),
(1, 4, 'Setup do servidor de produção',   'low',    '2025-01-30', 1, 0);

-- Assignees
INSERT INTO `item_assignees` VALUES (1,1),(2,2),(3,1),(4,3),(5,2),(6,1);

-- Comentários demo
INSERT INTO `comments` (`item_id`, `user_id`, `body`) VALUES
(3, 2, 'Iniciando o desenvolvimento. Vou usar CSS Grid para o layout.'),
(3, 1, '@João Silva ótimo! Lembre de testar em Safari também.'),
(5, 3, 'Revisão concluída, aprovado com pequenos ajustes.');

SET FOREIGN_KEY_CHECKS = 1;
