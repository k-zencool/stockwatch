-- 2026-11-12: "-" / "--" were typed as an employee code to mean "none"; employee_code is UNIQUE,
-- so the second "-" failed. Store those as NULL (no code) instead.
SET NAMES utf8mb4;
UPDATE employees SET employee_code = NULL
 WHERE employee_code IS NOT NULL AND employee_code REGEXP '^[[:space:]_.-]*$';
