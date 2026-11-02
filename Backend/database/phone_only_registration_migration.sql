-- Run once on databases that already have No_telepon from phone_login_migration.sql.
ALTER TABLE `user`
    MODIFY COLUMN `Email` varchar(255) DEFAULT NULL;
