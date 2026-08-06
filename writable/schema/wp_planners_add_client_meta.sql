-- Add client metadata columns to existing wp_planners tables.
-- Safe to run once; skip if columns already exist.

ALTER TABLE `wp_planners`
  ADD COLUMN `ip_address` varchar(45) DEFAULT NULL AFTER `project_plans_data`,
  ADD COLUMN `device` varchar(32) DEFAULT NULL AFTER `ip_address`,
  ADD COLUMN `user_agent` varchar(512) DEFAULT NULL AFTER `device`;
