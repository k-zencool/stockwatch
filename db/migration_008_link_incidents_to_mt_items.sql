-- 2026-11-12: after the MT stock list replaced the old items, re-link the existing incidents
-- to their catalogue item (item_id, category_id, item_name) by item code.
SET NAMES utf8mb4;
UPDATE incidents inc
  JOIN (
        SELECT 4 AS incident_id, 'MT-บ-0001' AS code
  UNION SELECT 5, 'MT-S-0016'
  UNION SELECT 6, 'MT-R-0001'   -- recorded as "ROTARY WARNING LIGHT DC220"
  UNION SELECT 7, 'MT-P-0006'
  UNION SELECT 8, 'MT-ส-0022'
  UNION SELECT 9, 'MT-ส-0024'
  ) m ON m.incident_id = inc.id
  JOIN items it ON it.code = m.code COLLATE utf8mb4_0900_ai_ci
   SET inc.item_id = it.id, inc.category_id = it.category_id, inc.item_name = it.name;
