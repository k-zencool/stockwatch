-- 2026-11-12: storage location (shelf/bin, e.g. "A3-3") for each item in the warehouse.
SET NAMES utf8mb4;
ALTER TABLE items ADD COLUMN shelf VARCHAR(30) NULL AFTER code;
