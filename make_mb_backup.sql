-- run once as an admin
CREATE USER 'mb_backup'@'localhost' IDENTIFIED BY 'Ibackthingsupandputthemd0wn$';
GRANT SELECT, SHOW VIEW, LOCK TABLES, TRIGGER, EVENT ON `animalbook`.* TO 'mb_backup'@'localhost';
GRANT SELECT, SHOW VIEW, LOCK TABLES, TRIGGER, EVENT ON `userbook`.*   TO 'mb_backup'@'localhost';
-- dumping stored procedures/functions (--routines) needs routine visibility:
GRANT SELECT ON `mysql`.`proc` TO 'mb_backup'@'localhost'; -- MySQL 8 may instead require:
-- GRANT SHOW_ROUTINE ON *.* TO 'mb_backup'@'localhost';
FLUSH PRIVILEGES;
