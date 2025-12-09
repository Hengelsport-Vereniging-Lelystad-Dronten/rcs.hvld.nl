-- Create reports table for periodic reporting feature
-- Run this SQL directly in your MariaDB database if php artisan migrate cannot be executed

CREATE TABLE IF NOT EXISTS reports (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  report_type enum('weekly','monthly','quarterly','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'weekly' COMMENT 'Type van periodieke rapportage',
  period_start datetime NOT NULL COMMENT 'Start van rapportageperiode',
  period_end datetime NOT NULL COMMENT 'Eind van rapportageperiode',
  data_summary json DEFAULT NULL COMMENT 'Samengevatte statistieken en KPI''s in JSON formaat',
  generated_at datetime DEFAULT NULL COMMENT 'Moment waarop rapport is gegenereerd',
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY reports_report_type_period_start_period_end_index (report_type,period_start,period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Also add migration tracking (if needed)
INSERT INTO migrations (migration, batch) VALUES ('2025_12_09_000000_create_reports_table', (SELECT MAX(batch) + 1 FROM (SELECT MAX(batch) as batch FROM migrations) t));
