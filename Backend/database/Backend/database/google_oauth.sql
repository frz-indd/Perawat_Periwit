USE eksplormajaku;

ALTER TABLE `user`
    MODIFY COLUMN Password VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS user_oauth_account (
    Id_oauth_account INT(12) NOT NULL AUTO_INCREMENT,
    Id_user INT(12) NOT NULL,
    provider VARCHAR(30) NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id_oauth_account),
    UNIQUE KEY uq_oauth_provider_subject (provider, provider_user_id),
    KEY idx_oauth_user (Id_user),
    CONSTRAINT fk_oauth_user FOREIGN KEY (Id_user)
        REFERENCES `user`(Id_user)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
