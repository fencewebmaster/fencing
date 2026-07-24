-- Soft-delete / trash for planner entries.
-- Safe to run once; skip if column already exists.
-- Also applied automatically via fc_planners_ensure_columns().

ALTER TABLE `wp_planners`
  ADD COLUMN `trashed_at` datetime DEFAULT NULL AFTER `updated_at`,
  ADD KEY `trashed_at` (`trashed_at`);
