-- 2026-11-12: the `reasons` list is now used as employee shifts (กะพนักงาน, label-only change;
-- table/column names unchanged). The reason for not requisitioning is typed freely per incident.
SET NAMES utf8mb4;
ALTER TABLE incidents ADD COLUMN reason_text VARCHAR(255) NULL AFTER reason_id;
