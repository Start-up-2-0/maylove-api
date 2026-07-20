<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Map\CoupleMapStatus;
use App\Domain\Map\MapStyle;
use App\Domain\Tribute\BillingModeUsed;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'couple_maps')]
#[ORM\UniqueConstraint(name: 'uniq_couple_maps_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_couple_maps_user_status', columns: ['user_id', 'status', 'updated_at'])]
#[ORM\HasLifecycleCallbacks]
class CoupleMap
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 30, enumType: CoupleMapStatus::class)]
    private CoupleMapStatus $status = CoupleMapStatus::Draft;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(name: 'couple_names', length: 255)]
    private string $coupleNames;

    #[ORM\Column(name: 'map_style', length: 32, enumType: MapStyle::class)]
    private MapStyle $mapStyle = MapStyle::Romantic;

    #[ORM\Column(name: 'show_route', type: Types::BOOLEAN)]
    private bool $showRoute = true;

    #[ORM\Column(name: 'center_lat', type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $centerLat = null;

    #[ORM\Column(name: 'center_lng', type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $centerLng = null;

    #[ORM\Column(name: 'default_zoom', type: Types::SMALLINT, nullable: true, options: ['unsigned' => true])]
    private ?int $defaultZoom = null;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'content_json', type: Types::JSON)]
    private array $contentJson = [];

    #[ORM\Column(name: 'billing_mode_used', length: 30, enumType: BillingModeUsed::class, nullable: true)]
    private ?BillingModeUsed $billingModeUsed = null;

    #[ORM\Column(name: 'views_count', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $viewsCount = 0;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(name: 'archived_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $slug, string $title, string $coupleNames)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->slug = $slug;
        $this->title = $title;
        $this->coupleNames = $coupleNames;
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

    public function getStatus(): CoupleMapStatus
    {
        return $this->status;
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable() && $this->archivedAt === null;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->touch();
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): void
    {
        $this->subtitle = $subtitle;
        $this->touch();
    }

    public function getCoupleNames(): string
    {
        return $this->coupleNames;
    }

    public function setCoupleNames(string $coupleNames): void
    {
        $this->coupleNames = $coupleNames;
        $this->touch();
    }

    public function getMapStyle(): MapStyle
    {
        return $this->mapStyle;
    }

    public function setMapStyle(MapStyle $mapStyle): void
    {
        $this->mapStyle = $mapStyle;
        $this->touch();
    }

    public function isShowRoute(): bool
    {
        return $this->showRoute;
    }

    public function setShowRoute(bool $showRoute): void
    {
        $this->showRoute = $showRoute;
        $this->touch();
    }

    public function getCenterLat(): ?float
    {
        return $this->centerLat !== null ? (float) $this->centerLat : null;
    }

    public function setCenterLat(?float $centerLat): void
    {
        $this->centerLat = $centerLat !== null ? (string) $centerLat : null;
        $this->touch();
    }

    public function getCenterLng(): ?float
    {
        return $this->centerLng !== null ? (float) $this->centerLng : null;
    }

    public function setCenterLng(?float $centerLng): void
    {
        $this->centerLng = $centerLng !== null ? (string) $centerLng : null;
        $this->touch();
    }

    public function getDefaultZoom(): ?int
    {
        return $this->defaultZoom;
    }

    public function setDefaultZoom(?int $defaultZoom): void
    {
        $this->defaultZoom = $defaultZoom;
        $this->touch();
    }

    /** @return array<string, mixed> */
    public function getContentJson(): array
    {
        return $this->contentJson;
    }

    /** @param array<string, mixed> $contentJson */
    public function setContentJson(array $contentJson): void
    {
        $this->contentJson = $contentJson;
        $this->touch();
    }

    public function getBillingModeUsed(): ?BillingModeUsed
    {
        return $this->billingModeUsed;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAwaitingPayment(): void
    {
        $this->status = CoupleMapStatus::AwaitingPayment;
        $this->touch();
    }

    public function markPublished(?BillingModeUsed $billingModeUsed = null): void
    {
        $this->status = CoupleMapStatus::Published;
        $this->publishedAt = new \DateTimeImmutable();
        $this->billingModeUsed = $billingModeUsed;
        $this->touch();
    }

    public function markArchived(): void
    {
        $this->status = CoupleMapStatus::Archived;
        $this->archivedAt = new \DateTimeImmutable();
        $this->touch();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
