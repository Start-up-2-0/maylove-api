<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Tribute\BillingModeUsed;
use App\Domain\Tribute\MusicSource;
use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Persistence\Entity\Catalog\Template;
use App\Infrastructure\Persistence\Entity\Catalog\TributeType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tributes')]
#[ORM\UniqueConstraint(name: 'uniq_tributes_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_tributes_user_status', columns: ['user_id', 'status', 'updated_at'])]
#[ORM\HasLifecycleCallbacks]
class Tribute
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: TributeType::class)]
    #[ORM\JoinColumn(name: 'tribute_type_id', referencedColumnName: 'id', nullable: false)]
    private TributeType $tributeType;

    #[ORM\ManyToOne(targetEntity: Template::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false)]
    private Template $template;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 30, enumType: TributeStatus::class)]
    private TributeStatus $status = TributeStatus::Draft;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(name: 'honoree_name', length: 120, nullable: true)]
    private ?string $honoreeName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'closing_message', type: Types::TEXT, nullable: true)]
    private ?string $closingMessage = null;

    #[ORM\Column(name: 'special_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $specialDate = null;

    #[ORM\Column(name: 'color_primary', length: 7, nullable: true)]
    private ?string $colorPrimary = null;

    #[ORM\Column(name: 'music_source', length: 20, enumType: MusicSource::class)]
    private MusicSource $musicSource = MusicSource::None;

    #[ORM\Column(name: 'music_track_id', type: 'uuid', nullable: true)]
    private ?Uuid $musicTrackId = null;

    #[ORM\Column(name: 'music_media_id', type: 'uuid', nullable: true)]
    private ?Uuid $musicMediaId = null;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'content_json', type: Types::JSON)]
    private array $contentJson = [];

    #[ORM\Column(name: 'billing_mode_used', length: 30, enumType: BillingModeUsed::class, nullable: true)]
    private ?BillingModeUsed $billingModeUsed = null;

    #[ORM\Column(name: 'views_count', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $viewsCount = 0;

    #[ORM\Column(name: 'og_image_url', length: 500, nullable: true)]
    private ?string $ogImageUrl = null;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        User $user,
        TributeType $tributeType,
        Template $template,
        string $slug,
    ) {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->tributeType = $tributeType;
        $this->template = $template;
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

    public function getTributeType(): TributeType
    {
        return $this->tributeType;
    }

    public function setTributeType(TributeType $tributeType): void
    {
        $this->tributeType = $tributeType;
        $this->touch();
    }

    public function getTemplate(): Template
    {
        return $this->template;
    }

    public function setTemplate(Template $template): void
    {
        $this->template = $template;
        $this->touch();
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

    public function getStatus(): TributeStatus
    {
        return $this->status;
    }

    public function setStatus(TributeStatus $status): void
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

    public function getHonoreeName(): ?string
    {
        return $this->honoreeName;
    }

    public function setHonoreeName(?string $honoreeName): void
    {
        $this->honoreeName = $honoreeName !== null ? trim($honoreeName) : null;
        $this->touch();
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
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

    public function getSpecialDate(): ?\DateTimeImmutable
    {
        return $this->specialDate;
    }

    public function setSpecialDate(?\DateTimeImmutable $specialDate): void
    {
        $this->specialDate = $specialDate;
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

    public function getMusicSource(): MusicSource
    {
        return $this->musicSource;
    }

    public function setMusicSource(MusicSource $musicSource): void
    {
        $this->musicSource = $musicSource;
        $this->touch();
    }

    public function getMusicTrackId(): ?Uuid
    {
        return $this->musicTrackId;
    }

    public function setMusicTrackId(?Uuid $musicTrackId): void
    {
        $this->musicTrackId = $musicTrackId;
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

    public function setBillingModeUsed(?BillingModeUsed $billingModeUsed): void
    {
        $this->billingModeUsed = $billingModeUsed;
        $this->touch();
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function getOgImageUrl(): ?string
    {
        return $this->ogImageUrl;
    }

    public function setOgImageUrl(?string $ogImageUrl): void
    {
        $this->ogImageUrl = $ogImageUrl;
        $this->touch();
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function markPublished(BillingModeUsed $billingMode): void
    {
        $this->status = TributeStatus::Published;
        $this->billingModeUsed = $billingMode;
        $this->publishedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function markAwaitingPayment(): void
    {
        $this->status = TributeStatus::AwaitingPayment;
        $this->touch();
    }

    public function markDraft(): void
    {
        $this->status = TributeStatus::Draft;
        $this->touch();
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function archive(): void
    {
        $this->status = TributeStatus::Archived;
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
    public function onPreUpdate(): void
    {
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
