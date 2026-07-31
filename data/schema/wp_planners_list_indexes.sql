-- Planner entries list performance indexes.
-- Safe to run once; skip any statement if SHOW INDEX already lists the key.
-- Also applied automatically via fc_planners_ensure_indexes().
--
-- Do NOT add UNIQUE(planner_id) here while duplicate planner_ids still exist.
-- Use data/schema/wp_planners_planner_id_unique.sql after Find Duplicates cleanup.

ALTER TABLE `wp_planners` ADD KEY `planner_id` (`planner_id`);
ALTER TABLE `wp_planners` ADD KEY `status` (`status`);
ALTER TABLE `wp_planners` ADD KEY `trashed_at` (`trashed_at`);

-- Default list: view=all + date range + ORDER BY created_at DESC, id DESC
ALTER TABLE `wp_planners` ADD KEY `idx_list_active_created` (`trashed_at`, `status`, `created_at`, `id`);

-- Tab badge counts: index-only scan of (trashed_at, status)
ALTER TABLE `wp_planners` ADD KEY `idx_trash_status` (`trashed_at`, `status`);

-- Trash / updated views
ALTER TABLE `wp_planners` ADD KEY `idx_list_updated` (`trashed_at`, `updated_at`, `id`);

-- Dashboard trend / recent ORDER BY created_at
ALTER TABLE `wp_planners` ADD KEY `idx_created_at` (`created_at`, `id`);

-- Dashboard top-customers GROUP BY email
ALTER TABLE `wp_planners` ADD KEY `idx_email` (`email`);
ALTER TABLE `wp_planners` ADD KEY `idx_email_cover` (`email`, `id`, `created_at`);
ALTER TABLE `wp_planners` ADD KEY `idx_state` (`state`);
ALTER TABLE `wp_planners` ADD KEY `idx_device` (`device`);
