<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Bouquet\DigitalBouquetStatus;
use App\Domain\Bouquet\LetterDesign;
use App\Domain\Bouquet\WrapColor;
use App\Domain\Tribute\BillingModeUsed;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'digital_bouquets')]
#[ORM\UniqueConstraint(name: 'uniq_digital_bouquets_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_digital_bouquets_user_status', columns: ['user_id', 'status', 'updated_at'])]
#[ORM\HasLifecycleCallbacks]
class DigitalBouquet
{
    public const MAX_STEMS = 7;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(length: 30, enumType: DigitalBouquetStatus::class)]
    private DigitalBouquetStatus $status = DigitalBouquetStatus::Draft;

    #[ORM\Column(length: 255)]
    private string $title;

    /** @var list<string> */
    #[ORM\Column(name: 'stems_json', type: Types::JSON)]
    private array $stemsJson = [];

    #[ORM\Column(name: 'wrap_color', length: 20, enumType: WrapColor::class)]
    private WrapColor $wrapColor = WrapColor::Blush;

    #[ORM\Column(name: 'recipient_name', length: 120)]
    private string $recipientName = '';

    #[ORM\Column(name: 'sender_name', length: 120)]
    private string $senderName = '';

    #[ORM\Column(name: 'letter_body', type: Types::TEXT)]
    private string $letterBody = '';

    #[ORM\Column(name: 'letter_design', length: 20, enumType: LetterDesign::class)]
    private LetterDesign $letterDesign = LetterDesign::Classic;

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

    public function __construct(User $user, string $slug, string $title)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->slug = $slug;
        $this->title = $title;
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

    public function getStatus(): DigitalBouquetStatus
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

    /** @return list<string> */
    public function getStemsJson(): array
    {
        return $this->stemsJson;
    }

    /** @param list<string> $stemsJson */
    public function setStemsJson(array $stemsJson): void
    {
        $this->stemsJson = array_values($stemsJson);
        $this->touch();
    }

    public function getWrapColor(): WrapColor
    {
        return $this->wrapColor;
    }

    public function setWrapColor(WrapColor $wrapColor): void
    {
        $this->wrapColor = $wrapColor;
        $this->touch();
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): void
    {
        $this->recipientName = $recipientName;
        $this->touch();
    }

    public function getSenderName(): string
    {
        return $this->senderName;
    }

    public function setSenderName(string $senderName): void
    {
        $this->senderName = $senderName;
        $this->touch();
    }

    public function getLetterBody(): string
    {
        return $this->letterBody;
    }

    public function setLetterBody(string $letterBody): void
    {
        $this->letterBody = $letterBody;
        $this->touch();
    }

    public function getLetterDesign(): LetterDesign
    {
        return $this->letterDesign;
    }

    public function setLetterDesign(LetterDesign $letterDesign): void
    {
        $this->letterDesign = $letterDesign;
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
        $this->status = DigitalBouquetStatus::AwaitingPayment;
        $this->touch();
    }

    public function markPublished(?BillingModeUsed $billingModeUsed = null): void
    {
        $this->status = DigitalBouquetStatus::Published;
        $this->publishedAt = new \DateTimeImmutable();
        $this->billingModeUsed = $billingModeUsed;
        $this->touch();
    }

    public function markArchived(): void
    {
        $this->status = DigitalBouquetStatus::Archived;
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
