-- Track how many times a quote was loaded via ?qid= or the Load Quote form.
-- Safe to run once; skip if column already exists.

ALTER TABLE `wp_planners`
  ADD COLUMN `quote_load_count` int unsigned NOT NULL DEFAULT 0 AFTER `user_agent`;
