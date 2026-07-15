<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Album\AlbumStatus;
use App\Domain\Album\BookPresentation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'albums')]
#[ORM\UniqueConstraint(name: 'uniq_albums_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_albums_user_status', columns: ['user_id', 'status', 'updated_at'])]
#[ORM\HasLifecycleCallbacks]
class Album
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 30, enumType: AlbumStatus::class)]
    private AlbumStatus $status = AlbumStatus::Draft;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(name: 'closing_message', type: Types::TEXT, nullable: true)]
    private ?string $closingMessage = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $signature = null;

    #[ORM\Column(name: 'color_primary', length: 7, nullable: true)]
    private ?string $colorPrimary = '#c45d7a';

    #[ORM\Column(length: 40, enumType: BookPresentation::class)]
    private BookPresentation $presentation = BookPresentation::ClassicPhotobook;

    #[ORM\Column(name: 'photos_per_page', type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 1])]
    private int $photosPerPage = 1;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'book_config', type: Types::JSON, nullable: true)]
    private ?array $bookConfig = null;

    /** @var list<array<string, mixed>>|null */
    #[ORM\Column(name: 'book_pages', type: Types::JSON, nullable: true)]
    private ?array $bookPages = null;

    #[ORM\Column(name: 'music_media_id', type: 'uuid', nullable: true)]
    private ?Uuid $musicMediaId = null;

    #[ORM\Column(name: 'is_public', type: Types::BOOLEAN)]
    private bool $isPublic = true;

    #[ORM\Column(name: 'views_count', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $viewsCount = 0;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $slug)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->slug = $slug;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
        $this->touch();
    }

    public function getStatus(): AlbumStatus
    {
        return $this->status;
    }

    public function setStatus(AlbumStatus $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable() && $this->deletedAt === null;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title !== null ? trim($title) : null;
        $this->touch();
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): void
    {
        $this->subtitle = $subtitle !== null ? trim($subtitle) : null;
        $this->touch();
    }

    public function getClosingMessage(): ?string
    {
        return $this->closingMessage;
    }

    public function setClosingMessage(?string $closingMessage): void
    {
        $this->closingMessage = $closingMessage;
        $this->touch();
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): void
    {
        $this->signature = $signature !== null ? trim($signature) : null;
        $this->touch();
    }

    public function getColorPrimary(): ?string
    {
        return $this->colorPrimary;
    }

    public function setColorPrimary(?string $colorPrimary): void
    {
        $this->colorPrimary = $colorPrimary;
        $this->touch();
    }

    public function getPresentation(): BookPresentation
    {
        return $this->presentation;
    }

    public function setPresentation(BookPresentation $presentation): void
    {
        $this->presentation = $presentation;
        $this->touch();
    }

    public function getPhotosPerPage(): int
    {
        return $this->photosPerPage;
    }

    public function setPhotosPerPage(int $photosPerPage): void
    {
        $this->photosPerPage = max(1, min(4, $photosPerPage));
        $this->touch();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBookConfig(): ?array
    {
        return $this->bookConfig;
    }

    /**
     * @param array<string, mixed>|null $bookConfig
     */
    public function setBookConfig(?array $bookConfig): void
    {
        $this->bookConfig = $bookConfig;
        $this->touch();
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function getBookPages(): ?array
    {
        return $this->bookPages;
    }

    /**
     * @param list<array<string, mixed>>|null $bookPages
     */
    public function setBookPages(?array $bookPages): void
    {
        $this->bookPages = $bookPages;
        $this->touch();
    }

    public function getMusicMediaId(): ?Uuid
    {
        return $this->musicMediaId;
    }

    public function setMusicMediaId(?Uuid $musicMediaId): void
    {
        $this->musicMediaId = $musicMediaId;
        $this->touch();
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
        $this->touch();
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function markPublished(): void
    {
        $this->status = AlbumStatus::Published;
        $this->publishedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function markAwaitingPayment(): void
    {
        $this->status = AlbumStatus::AwaitingPayment;
        $this->touch();
    }

    public function markDraft(): void
    {
        $this->status = AlbumStatus::Draft;
        $this->touch();
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
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
