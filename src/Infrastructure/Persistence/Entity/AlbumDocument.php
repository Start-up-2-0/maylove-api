<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'album_documents')]
#[ORM\Index(name: 'idx_album_documents_album', columns: ['album_id'])]
class AlbumDocument
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Album $album;

    #[ORM\ManyToOne(targetEntity: AlbumMemory::class)]
    #[ORM\JoinColumn(name: 'memory_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?AlbumMemory $memory = null;

    #[ORM\Column(name: 'storage_file_id', length: 64)]
    private string $storageFileId;

    #[ORM\Column(name: 'doc_type', length: 32, options: ['default' => 'other'])]
    private string $docType = 'other';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Album $album, string $storageFileId, string $docType = 'other')
    {
        $this->id = Uuid::v7();
        $this->album = $album;
        $this->storageFileId = $storageFileId;
        $this->docType = $docType;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAlbum(): Album
    {
        return $this->album;
    }

    public function getMemory(): ?AlbumMemory
    {
        return $this->memory;
    }

    public function setMemory(?AlbumMemory $memory): void
    {
        $this->memory = $memory;
    }

    public function getStorageFileId(): string
    {
        return $this->storageFileId;
    }

    public function getDocType(): string
    {
        return $this->docType;
    }

    public function setDocType(string $docType): void
    {
        $this->docType = $docType;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label !== null ? trim($label) : null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
