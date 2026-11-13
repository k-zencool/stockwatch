-- 2026-11-13: stock counts recorded by scanning a shelf QR and typing the counted quantity.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS stock_counts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    qty INT NOT NULL,
    counted_by INT NULL,
    counted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_item_time (item_id, counted_at),
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (counted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
