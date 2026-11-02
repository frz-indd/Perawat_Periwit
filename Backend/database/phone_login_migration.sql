-- Run once on existing databases before allowing phone-number registration.
ALTER TABLE `user`
    ADD COLUMN `No_telepon` varchar(20) DEFAULT NULL AFTER `Email`,
    ADD UNIQUE KEY `uq_user_no_telepon` (`No_telepon`);

ALTER TABLE `user`
    MODIFY COLUMN `Email` varchar(255) DEFAULT NULL;
