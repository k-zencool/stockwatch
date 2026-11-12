-- ระบบติดตามการไม่เบิกของ (StockWatch)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    username VARCHAR(50) NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS item_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT NULL,
    code VARCHAR(50) NULL,
    shelf VARCHAR(30) NULL,
    unit VARCHAR(30) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES item_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NULL,
    external_name VARCHAR(100) NULL,
    external_department VARCHAR(100) NULL,
    item_id INT NULL,
    category_id INT NULL,
    reason_id INT NULL,          -- shown as กะพนักงาน (employee shift)
    reason_text VARCHAR(255) NULL, -- reason typed by the user
    item_name VARCHAR(150) NOT NULL,
    incident_date DATE NOT NULL,
    note TEXT NULL,
    recorded_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES item_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (reason_id) REFERENCES reasons(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_incident_date (incident_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO reasons (name) VALUES
('ลืมเบิกเอกสาร/ไม่ได้แจ้ง'),
('รีบใช้งานฉุกเฉิน ไม่ทันเบิก'),
('ไม่ทราบขั้นตอนการเบิก'),
('ผู้ดูแลคลังไม่อยู่/หาไม่เจอ'),
('คิดว่าเบิกแล้ว/เข้าใจผิด'),
('อื่นๆ');

INSERT INTO item_categories (name) VALUES
('เครื่องเขียน'),
('อุปกรณ์ไฟฟ้า'),
('เครื่องมือช่าง'),
('อุปกรณ์ไอที'),
('อุปกรณ์สำนักงานอื่นๆ');

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

INSERT INTO employees (employee_code, full_name, department, position) VALUES
('EMP01', 'พนักงาน 1', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP02', 'พนักงาน 2', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP03', 'พนักงาน 3', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP04', 'พนักงาน 4', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP05', 'พนักงาน 5', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP06', 'พนักงาน 6', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP07', 'พนักงาน 7', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP08', 'พนักงาน 8', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP09', 'พนักงาน 9', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP10', 'พนักงาน 10', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP11', 'พนักงาน 11', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP12', 'พนักงาน 12', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP13', 'พนักงาน 13', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP14', 'พนักงาน 14', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP15', 'พนักงาน 15', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP16', 'พนักงาน 16', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP17', 'พนักงาน 17', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP18', 'พนักงาน 18', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP19', 'พนักงาน 19', 'ทั่วไป', 'เจ้าหน้าที่'),
('EMP20', 'พนักงาน 20', 'ทั่วไป', 'เจ้าหน้าที่');
