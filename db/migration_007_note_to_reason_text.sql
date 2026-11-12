-- 2026-11-12: incidents recorded before reason_text existed kept the reason in `note`.
-- Move it into reason_text (only where reason_text is still empty) and clear note.
SET NAMES utf8mb4;
UPDATE incidents
   SET reason_text = LEFT(TRIM(REPLACE(note, '  ', ' ')), 255), note = NULL
 WHERE (reason_text IS NULL OR reason_text = '')
   AND note IS NOT NULL AND TRIM(note) <> '';
