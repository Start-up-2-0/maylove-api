<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas de billing/pagamentos com seeds de produto premium.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE products (
                id CHAR(36) NOT NULL,
                slug VARCHAR(80) NOT NULL,
                name VARCHAR(120) NOT NULL,
                features_json JSON NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                UNIQUE INDEX uniq_products_slug (slug),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE product_prices (
                id CHAR(36) NOT NULL,
                product_id CHAR(36) NOT NULL,
                billing_mode VARCHAR(30) NOT NULL,
                price_cents INT NOT NULL,
                mp_plan_id VARCHAR(120) DEFAULT NULL,
                UNIQUE INDEX uniq_product_prices_mode (product_id, billing_mode),
                PRIMARY KEY (id),
                CONSTRAINT fk_product_prices_product FOREIGN KEY (product_id) REFERENCES products (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE user_subscriptions (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                product_id CHAR(36) NOT NULL,
                product_price_id CHAR(36) NOT NULL,
                status VARCHAR(30) NOT NULL,
                mp_subscription_id VARCHAR(120) DEFAULT NULL,
                mp_preference_id VARCHAR(120) DEFAULT NULL,
                checkout_url VARCHAR(500) DEFAULT NULL,
                current_period_start DATETIME(6) DEFAULT NULL,
                current_period_end DATETIME(6) DEFAULT NULL,
                cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_user_subscriptions_user (user_id),
                UNIQUE INDEX uniq_user_subscriptions_mp (mp_subscription_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_user_subscriptions_user FOREIGN KEY (user_id) REFERENCES users (id),
                CONSTRAINT fk_user_subscriptions_product FOREIGN KEY (product_id) REFERENCES products (id),
                CONSTRAINT fk_user_subscriptions_price FOREIGN KEY (product_price_id) REFERENCES product_prices (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE orders (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                tribute_id CHAR(36) NOT NULL,
                product_price_id CHAR(36) NOT NULL,
                status VARCHAR(30) NOT NULL,
                amount_cents INT NOT NULL,
                mp_preference_id VARCHAR(120) DEFAULT NULL,
                checkout_url VARCHAR(500) DEFAULT NULL,
                external_reference VARCHAR(120) NOT NULL,
                paid_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_orders_tribute (tribute_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id),
                CONSTRAINT fk_orders_tribute FOREIGN KEY (tribute_id) REFERENCES tributes (id),
                CONSTRAINT fk_orders_price FOREIGN KEY (product_price_id) REFERENCES product_prices (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE payments (
                id CHAR(36) NOT NULL,
                order_id CHAR(36) DEFAULT NULL,
                user_subscription_id CHAR(36) DEFAULT NULL,
                mp_payment_id VARCHAR(120) NOT NULL,
                status VARCHAR(30) NOT NULL,
                amount_cents INT NOT NULL,
                raw_response JSON DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_payments_mp (mp_payment_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL,
                CONSTRAINT fk_payments_subscription FOREIGN KEY (user_subscription_id) REFERENCES user_subscriptions (id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE payment_events (
                id CHAR(36) NOT NULL,
                external_event_id VARCHAR(150) NOT NULL,
                event_type VARCHAR(120) NOT NULL,
                payload JSON NOT NULL,
                processed_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_payment_events_external (external_event_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->seedProducts();
    }

    private function seedProducts(): void
    {
        $productId = '01940000-0000-7000-8000-000000000001';
        $pricePerTribute = '01940000-0000-7000-8000-000000000002';
        $priceMonthly = '01940000-0000-7000-8000-000000000003';
        $priceYearly = '01940000-0000-7000-8000-000000000004';

        $features = json_encode([
            'unlimited_tributes' => true,
            'premium_templates' => true,
            'music_library' => true,
            'visual_effects' => true,
        ], JSON_THROW_ON_ERROR);

        $this->addSql(
            'INSERT INTO products (id, slug, name, features_json, is_active) VALUES (?, ?, ?, ?, 1)',
            [$productId, 'premium', 'MayLove Premium', $features],
        );

        $this->addSql(
            'INSERT INTO product_prices (id, product_id, billing_mode, price_cents, mp_plan_id) VALUES (?, ?, ?, ?, NULL)',
            [$pricePerTribute, $productId, 'per_tribute', 599],
        );
        $this->addSql(
            'INSERT INTO product_prices (id, product_id, billing_mode, price_cents, mp_plan_id) VALUES (?, ?, ?, ?, NULL)',
            [$priceMonthly, $productId, 'subscription_monthly', 2400],
        );
        $this->addSql(
            'INSERT INTO product_prices (id, product_id, billing_mode, price_cents, mp_plan_id) VALUES (?, ?, ?, ?, NULL)',
            [$priceYearly, $productId, 'subscription_yearly', 28800],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payment_events');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE user_subscriptions');
        $this->addSql('DROP TABLE product_prices');
        $this->addSql('DROP TABLE products');
    }
}
