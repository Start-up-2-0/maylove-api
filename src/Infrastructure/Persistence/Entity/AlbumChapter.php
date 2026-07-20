<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'album_chapters')]
#[ORM\Index(name: 'idx_album_chapters_album', columns: ['album_id', 'sort_order'])]
class AlbumChapter
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Album $album;

    #[ORM\Column(length: 200)]
    private string $title;

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Album $album, string $title, int $sortOrder = 0)
    {
        $this->id = Uuid::v7();
        $this->album = $album;
        $this->title = trim($title);
        $this->sortOrder = $sortOrder;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAlbum(): Album
    {
        return $this->album;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
        $this->touch();
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
        $this->touch();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
