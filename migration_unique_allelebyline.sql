-- migration_unique_allelebyline.sql
-- Milestone 1 / M1-B (issue #25): prevent duplicate per-line allele assignments.
--
-- `key_allelebyline` shipped with only a surrogate `id` PRIMARY KEY and no
-- natural uniqueness on (`line`,`allelegroup`). Re-adding (or double-adding) an
-- allele group to a line therefore created duplicate assignment rows, which
-- pollute assignment counts and the admin repair surface (M1-E).
--
-- This migration (1) removes any existing duplicate (line, allelegroup) rows,
-- keeping the lowest `id`, then (2) adds a UNIQUE index as a backstop for the
-- application-level guard added to php/manage_lines.php.
--
-- IDEMPOTENT: safe to re-run. The dedupe finds nothing on a clean table and the
-- index is only added when absent.
--
-- PORTABILITY (M1-G): written to run on both MySQL 8.0/8.4 and MariaDB 10.11+.
-- The index-existence check via information_schema + PREPARE avoids engine-
-- specific `CREATE INDEX ... IF NOT EXISTS` syntax differences.
--
-- KEY-LENGTH NOTE: `key_allelebyline` is ENGINE=MyISAM with utf8mb3 columns.
-- MyISAM caps an index key at 1000 bytes; two full varchar(255) utf8mb3 columns
-- would need 2 x 255 x 3 = 1530 bytes and be rejected. We therefore index a
-- 166-char prefix of each column (166 x 3 x 2 = 996 bytes, under the limit).
-- Line and allele-group identifiers are short lab labels, so the prefix is
-- exact in practice; the full-value check lives in the application guard.
--
-- DEPLOY ORDER: apply this migration BEFORE (or with) the M1-B PHP patch. Run it
-- against each colony database (e.g. animalbook), not userbook.

-- (1) Remove duplicate assignments, keeping the lowest id per (line, allelegroup).
DELETE k1 FROM `key_allelebyline` k1
JOIN `key_allelebyline` k2
  ON k1.`line` = k2.`line`
 AND k1.`allelegroup` = k2.`allelegroup`
 AND k1.`id` > k2.`id`;

-- (2) Add the UNIQUE index only if it is not already present.
SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'key_allelebyline'
    AND index_name = 'uniq_line_allelegroup'
);
SET @ddl := IF(@idx_exists = 0,
  'ALTER TABLE `key_allelebyline` ADD UNIQUE INDEX `uniq_line_allelegroup` (`line`(166), `allelegroup`(166))',
  'DO 0');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
