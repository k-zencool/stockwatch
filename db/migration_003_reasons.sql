SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE incidents ADD COLUMN reason_id INT NULL AFTER category_id;
ALTER TABLE incidents ADD CONSTRAINT fk_incidents_reason FOREIGN KEY (reason_id) REFERENCES reasons(id) ON DELETE SET NULL;

INSERT INTO reasons (name) VALUES
('ลืมเบิกเอกสาร/ไม่ได้แจ้ง'),
('รีบใช้งานฉุกเฉิน ไม่ทันเบิก'),
('ไม่ทราบขั้นตอนการเบิก'),
('ผู้ดูแลคลังไม่อยู่/หาไม่เจอ'),
('คิดว่าเบิกแล้ว/เข้าใจผิด'),
('อื่นๆ');
