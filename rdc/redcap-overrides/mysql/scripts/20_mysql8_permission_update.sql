-- This is a 'quick-fix' for mysql8 which uses a different password plugin (caching_sha2_password) instead of the old
-- (mysql_native_password).  Currently (10/2019) the mysqli interface in php doesn't support sha2 logins, so for
-- REDCap (and PHP) to be able to log in, we need to change the password plugin.
--
-- ALTER USER 'redcap'@'%' IDENTIFIED WITH mysql_native_password BY 'redcap123';
-- ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY 'root';
--
-- COMMANDS LIKE THE ONES ABOVE SHOULD BE INSERTED BY THE SHELL SCRIPT BEFORE THIS FILE

