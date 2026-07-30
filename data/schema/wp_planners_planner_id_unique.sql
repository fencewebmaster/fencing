-- Make `planner_id` unique so two concurrent saves can never insert twin rows for one quote.
-- Existing tables shipped a plain KEY, so run the check below BEFORE the ALTER.

-- 1. Must return zero rows. Anything listed is a real duplicate that has to be merged or
--    trashed first (keep the row with the richest cart_data / newest updated_at).
SELECT `planner_id`, COUNT(*) AS `rows`, GROUP_CONCAT(`id` ORDER BY `id`) AS `row_ids`
FROM `wp_planners`
GROUP BY `planner_id`
HAVING `rows` > 1;

-- 2. Blank ids cannot coexist under a unique index either — give them a value first.
SELECT `id`, `created_at` FROM `wp_planners` WHERE TRIM(`planner_id`) = '';

-- 3. Then swap the index (skip if SHOW INDEX already reports Non_unique = 0).
ALTER TABLE `wp_planners`
  DROP INDEX `planner_id`,
  ADD UNIQUE KEY `planner_id` (`planner_id`);
