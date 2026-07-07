<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas de usuários, sessões de autenticação e tokens de verificação/reset.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id CHAR(36) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(120) NOT NULL,
                email_verified_at DATETIME(6) DEFAULT NULL,
                status VARCHAR(30) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'user',
                login_attempts SMALLINT NOT NULL DEFAULT 0,
                locked_until DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_users_email (email),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE auth_sessions (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                access_token_hash CHAR(64) NOT NULL,
                refresh_token_hash CHAR(64) NOT NULL,
                login_at DATETIME(6) NOT NULL,
                access_token_expires_at DATETIME(6) NOT NULL,
                expires_at DATETIME(6) NOT NULL,
                last_renewed_at DATETIME(6) DEFAULT NULL,
                revoked_at DATETIME(6) DEFAULT NULL,
                ip VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                metadata_json JSON NOT NULL,
                created_at DATETIME(6) NOT NULL,
                INDEX idx_auth_sessions_access_token_hash (access_token_hash),
                INDEX idx_auth_sessions_refresh_token_hash (refresh_token_hash),
                INDEX idx_auth_sessions_user_revoked (user_id, revoked_at),
                PRIMARY KEY (id),
                CONSTRAINT fk_auth_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE email_verification_tokens (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME(6) NOT NULL,
                used_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_email_verification_token_hash (token_hash),
                PRIMARY KEY (id),
                CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE password_reset_tokens (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME(6) NOT NULL,
                used_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_password_reset_token_hash (token_hash),
                PRIMARY KEY (id),
                CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE email_verification_tokens');
        $this->addSql('DROP TABLE auth_sessions');
        $this->addSql('DROP TABLE users');
    }
}
