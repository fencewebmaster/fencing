-- Track when the early Zapier webhook (form_submission / cart_submission trigger,
-- see PlannerWebhookService) last fired for a planner row, so a second near-
-- simultaneous request within the configured reset window doesn't double-fire.
-- Safe to run once; skip if column already exists.

ALTER TABLE `wp_planners`
  ADD COLUMN `webhook_sent_at` datetime DEFAULT NULL AFTER `trashed_at`;
