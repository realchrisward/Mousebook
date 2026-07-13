-- run once as an admin
-- SET YOUR OWN PASSWORD BELOW BEFORE RUNNING THIS FILE.
--
-- This script previously shipped a literal password. Mousebook is a public
-- repository: a password committed here is a password everyone has. Worse, this
-- file sits in the webroot on a typical deployment and was fetchable over HTTP.
--
-- If you ran the previous version as-is, the mb_backup account on that server
-- has a publicly known password and SELECT on both databases. Rotate it:
--     ALTER USER 'mb_backup'@'localhost' IDENTIFIED BY '<new password>';
-- and update whatever backup job uses it.
--
-- Generate a password:   openssl rand -base64 24
CREATE USER 'mb_backup'@'localhost' IDENTIFIED BY 'CHANGE_ME_BEFORE_RUNNING';
GRANT SELECT, SHOW VIEW, LOCK TABLES, TRIGGER, EVENT ON `animalbook`.* TO 'mb_backup'@'localhost';
GRANT SELECT, SHOW VIEW, LOCK TABLES, TRIGGER, EVENT ON `userbook`.*   TO 'mb_backup'@'localhost';
-- dumping stored procedures/functions (--routines) needs routine visibility:
GRANT SELECT ON `mysql`.`proc` TO 'mb_backup'@'localhost'; -- MySQL 8 may instead require:
-- GRANT SHOW_ROUTINE ON *.* TO 'mb_backup'@'localhost';
FLUSH PRIVILEGES;
