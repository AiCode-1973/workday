-- Migração 004: dados de ferramenta por item
CREATE TABLE IF NOT EXISTS item_tool_data (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    item_id    INT NOT NULL,
    tool_type  VARCHAR(50) NOT NULL DEFAULT 'sipoc',
    content    LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item_tool (item_id, tool_type),
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
