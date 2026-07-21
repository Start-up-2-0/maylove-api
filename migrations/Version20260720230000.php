<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Buquê Digital: digital_bouquets, orders.digital_bouquet_id e seed per_bouquet';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS digital_bouquets (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'draft',
                title VARCHAR(255) NOT NULL,
                stems_json JSON NOT NULL,
                wrap_color VARCHAR(20) NOT NULL DEFAULT 'blush',
                recipient_name VARCHAR(120) NOT NULL DEFAULT '',
                sender_name VARCHAR(120) NOT NULL DEFAULT '',
                letter_body LONGTEXT NOT NULL,
                letter_design VARCHAR(20) NOT NULL DEFAULT 'classic',
                content_json JSON NOT NULL,
                billing_mode_used VARCHAR(30) DEFAULT NULL,
                views_count INT UNSIGNED NOT NULL DEFAULT 0,
                published_at DATETIME(6) DEFAULT NULL,
                archived_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX uniq_digital_bouquets_slug (slug),
                INDEX idx_digital_bouquets_user_status (user_id, status, updated_at),
                CONSTRAINT FK_digital_bouquets_user FOREIGN KEY (user_id) REFERENCES users (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        if (!$this->columnExists('orders', 'digital_bouquet_id')) {
            $this->addSql('ALTER TABLE orders ADD digital_bouquet_id CHAR(36) DEFAULT NULL');
        }

        if (!$this->indexExists('orders', 'uniq_orders_digital_bouquet')) {
            $this->addSql('CREATE UNIQUE INDEX uniq_orders_digital_bouquet ON orders (digital_bouquet_id)');
        }

        if (!$this->foreignKeyExists('orders', 'FK_orders_digital_bouquet')) {
            $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_orders_digital_bouquet FOREIGN KEY (digital_bouquet_id) REFERENCES digital_bouquets (id)');
        }

        $pricePerBouquet = '01940000-0000-7000-8000-000000000007';
        $productId = '01940000-0000-7000-8000-000000000001';
        $this->addSql(
            'INSERT INTO product_prices (id, product_id, billing_mode, price_cents, mp_plan_id)
             SELECT ?, ?, ?, ?, NULL
             WHERE NOT EXISTS (
               SELECT 1 FROM product_prices WHERE product_id = ? AND billing_mode = ?
             )',
            [$pricePerBouquet, $productId, 'per_bouquet', 599, $productId, 'per_bouquet'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM product_prices WHERE billing_mode = 'per_bouquet'");

        if ($this->foreignKeyExists('orders', 'FK_orders_digital_bouquet')) {
            $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_orders_digital_bouquet');
        }

        if ($this->indexExists('orders', 'uniq_orders_digital_bouquet')) {
            $this->addSql('DROP INDEX uniq_orders_digital_bouquet ON orders');
        }

        if ($this->columnExists('orders', 'digital_bouquet_id')) {
            $this->addSql('ALTER TABLE orders DROP digital_bouquet_id');
        }

        $this->addSql('DROP TABLE IF EXISTS digital_bouquets');
    }

    private function columnExists(string $table, string $column): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );

        return (int) $count > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index],
        );

        return (int) $count > 0;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY'],
        );

        return (int) $count > 0;
    }
}
