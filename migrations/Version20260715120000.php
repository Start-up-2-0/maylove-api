<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Billing: preço tribute 4,99, álbum 10,99, order polimórfico e dados PIX.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_orders_tribute');
        $this->addSql('ALTER TABLE orders DROP INDEX uniq_orders_tribute');
        $this->addSql('ALTER TABLE orders CHANGE tribute_id tribute_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD album_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD pix_qr_code LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD pix_qr_code_base64 LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD pix_ticket_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD mp_payment_id VARCHAR(120) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_orders_tribute ON orders (tribute_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_orders_album ON orders (album_id)');
        $this->addSql(
            'ALTER TABLE orders ADD CONSTRAINT fk_orders_tribute FOREIGN KEY (tribute_id) REFERENCES tributes (id)',
        );
        $this->addSql(
            'ALTER TABLE orders ADD CONSTRAINT fk_orders_album FOREIGN KEY (album_id) REFERENCES albums (id)',
        );

        $this->addSql(
            "UPDATE product_prices SET price_cents = 499 WHERE billing_mode = 'per_tribute'",
        );

        $productId = '01940000-0000-7000-8000-000000000001';
        $pricePerAlbum = '01940000-0000-7000-8000-000000000005';
        $this->addSql(
            'INSERT INTO product_prices (id, product_id, billing_mode, price_cents, mp_plan_id)
             SELECT ?, ?, ?, ?, NULL
             WHERE NOT EXISTS (
               SELECT 1 FROM product_prices WHERE product_id = ? AND billing_mode = ?
             )',
            [$pricePerAlbum, $productId, 'per_album', 1099, $productId, 'per_album'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM product_prices WHERE billing_mode = 'per_album'");
        $this->addSql(
            "UPDATE product_prices SET price_cents = 599 WHERE billing_mode = 'per_tribute'",
        );

        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_orders_album');
        $this->addSql('ALTER TABLE orders DROP INDEX uniq_orders_album');
        $this->addSql('ALTER TABLE orders DROP COLUMN album_id');
        $this->addSql('ALTER TABLE orders DROP COLUMN pix_qr_code');
        $this->addSql('ALTER TABLE orders DROP COLUMN pix_qr_code_base64');
        $this->addSql('ALTER TABLE orders DROP COLUMN pix_ticket_url');
        $this->addSql('ALTER TABLE orders DROP COLUMN mp_payment_id');

        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY fk_orders_tribute');
        $this->addSql('ALTER TABLE orders DROP INDEX uniq_orders_tribute');
        $this->addSql('ALTER TABLE orders CHANGE tribute_id tribute_id CHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_orders_tribute ON orders (tribute_id)');
        $this->addSql(
            'ALTER TABLE orders ADD CONSTRAINT fk_orders_tribute FOREIGN KEY (tribute_id) REFERENCES tributes (id)',
        );
    }
}
