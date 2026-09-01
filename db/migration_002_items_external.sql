SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT NULL,
    code VARCHAR(50) NULL,
    unit VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES item_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE incidents MODIFY employee_id INT NULL;
ALTER TABLE incidents ADD COLUMN external_name VARCHAR(100) NULL AFTER employee_id;
ALTER TABLE incidents ADD COLUMN external_department VARCHAR(100) NULL AFTER external_name;
ALTER TABLE incidents ADD COLUMN item_id INT NULL AFTER external_department;
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL;

INSERT INTO items (name, category_id, code, unit) VALUES
('ปากกาลูกลื่น', 1, 'STA-001', 'ด้าม'),
('ดินสอไม้', 1, 'STA-002', 'แท่ง'),
('กระดาษ A4', 1, 'STA-003', 'รีม'),
('ยางลบ', 1, 'STA-004', 'ก้อน'),
('ปลั๊กไฟ', 2, 'ELE-001', 'ตัว'),
('สายไฟ VCT', 2, 'ELE-002', 'เมตร'),
('หลอดไฟ LED', 2, 'ELE-003', 'หลอด'),
('ไขควงแฉก', 3, 'TOL-001', 'ตัว'),
('คีมปากจิ้งจก', 3, 'TOL-002', 'ตัว'),
('เทปพันสายไฟ', 3, 'TOL-003', 'ม้วน'),
('เมาส์คอมพิวเตอร์', 4, 'IT-001', 'ตัว'),
('คีย์บอร์ด', 4, 'IT-002', 'ตัว'),
('สาย HDMI', 4, 'IT-003', 'เส้น'),
('แฟ้มเอกสาร', 5, 'OFF-001', 'แฟ้ม'),
('คลิปหนีบกระดาษ', 5, 'OFF-002', 'กล่อง');
