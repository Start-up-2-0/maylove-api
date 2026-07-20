<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mapa do Casal: couple_maps, map_places, map_place_media e orders.couple_map_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS couple_maps (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'draft',
                title VARCHAR(255) NOT NULL,
                subtitle VARCHAR(255) DEFAULT NULL,
                couple_names VARCHAR(255) NOT NULL,
                map_style VARCHAR(32) NOT NULL DEFAULT 'romantic',
                show_route TINYINT(1) NOT NULL DEFAULT 1,
                center_lat DECIMAL(10, 8) DEFAULT NULL,
                center_lng DECIMAL(11, 8) DEFAULT NULL,
                default_zoom SMALLINT UNSIGNED DEFAULT NULL,
                content_json JSON NOT NULL,
                billing_mode_used VARCHAR(30) DEFAULT NULL,
                views_count INT UNSIGNED NOT NULL DEFAULT 0,
                published_at DATETIME(6) DEFAULT NULL,
                archived_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                PRIMARY KEY(id),
                UNIQUE INDEX uniq_couple_maps_slug (slug),
                INDEX idx_couple_maps_user_status (user_id, status, updated_at),
                CONSTRAINT FK_couple_maps_user FOREIGN KEY (user_id) REFERENCES users (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS map_places (
                id CHAR(36) NOT NULL,
                couple_map_id CHAR(36) NOT NULL,
                place_type VARCHAR(32) NOT NULL,
                title VARCHAR(255) NOT NULL,
                subtitle VARCHAR(255) DEFAULT NULL,
                description LONGTEXT DEFAULT NULL,
                memory_date DATE DEFAULT NULL,
                memory_time TIME DEFAULT NULL,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                address_label VARCHAR(255) DEFAULT NULL,
                city VARCHAR(120) DEFAULT NULL,
                country CHAR(2) DEFAULT NULL,
                sentiment VARCHAR(32) DEFAULT NULL,
                is_highlight TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                content_json JSON NOT NULL,
                deleted_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_map_places_map (couple_map_id, sort_order),
                CONSTRAINT FK_map_places_map FOREIGN KEY (couple_map_id) REFERENCES couple_maps (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS map_place_media (
                id CHAR(36) NOT NULL,
                couple_map_id CHAR(36) NOT NULL,
                place_id CHAR(36) NOT NULL,
                storage_file_id VARCHAR(64) NOT NULL,
                media_type VARCHAR(20) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) DEFAULT NULL,
                size_bytes INT UNSIGNED DEFAULT NULL,
                url_thumbnail VARCHAR(500) DEFAULT NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                deleted_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_map_place_media_place (place_id, sort_order),
                CONSTRAINT FK_map_place_media_map FOREIGN KEY (couple_map_id) REFERENCES couple_maps (id) ON DELETE CASCADE,
                CONSTRAINT FK_map_place_media_place FOREIGN KEY (place_id) REFERENCES map_places (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);

        if (!$this->columnExists('orders', 'couple_map_id')) {
            $this->addSql('ALTER TABLE orders ADD couple_map_id CHAR(36) DEFAULT NULL');
        }

        if (!$this->indexExists('orders', 'uniq_orders_couple_map')) {
            $this->addSql('CREATE UNIQUE INDEX uniq_orders_couple_map ON orders (couple_map_id)');
        }

        if (!$this->foreignKeyExists('orders', 'FK_orders_couple_map')) {
            $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_orders_couple_map FOREIGN KEY (couple_map_id) REFERENCES couple_maps (id)');
        }

        $pricePerMap = '01940000-0000-7000-8000-000000000006';
        $productId = '01940000-0000-7000-8000-000000000001';
        $this->addSql(
            'INSERT INTO product_prices (id, product_id, billing_mode, price_cents, mp_plan_id)
             SELECT ?, ?, ?, ?, NULL
             WHERE NOT EXISTS (
               SELECT 1 FROM product_prices WHERE product_id = ? AND billing_mode = ?
             )',
            [$pricePerMap, $productId, 'per_map', 599, $productId, 'per_map'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM product_prices WHERE billing_mode = 'per_map'");

        if ($this->foreignKeyExists('orders', 'FK_orders_couple_map')) {
            $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_orders_couple_map');
        }

        if ($this->indexExists('orders', 'uniq_orders_couple_map')) {
            $this->addSql('DROP INDEX uniq_orders_couple_map ON orders');
        }

        if ($this->columnExists('orders', 'couple_map_id')) {
            $this->addSql('ALTER TABLE orders DROP couple_map_id');
        }

        $this->addSql('DROP TABLE IF EXISTS map_place_media');
        $this->addSql('DROP TABLE IF EXISTS map_places');
        $this->addSql('DROP TABLE IF EXISTS couple_maps');
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
