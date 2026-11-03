-- Run once after phone_login_migration.sql to allow phone-only accounts.
ALTER TABLE `user`
    MODIFY COLUMN `Email` varchar(255) DEFAULT NULL;
