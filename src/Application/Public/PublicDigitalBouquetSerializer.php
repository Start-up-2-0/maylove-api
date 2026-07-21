<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Infrastructure\Persistence\Entity\DigitalBouquet;

final class PublicDigitalBouquetSerializer
{
    /** @return array<string, mixed> */
    public function serialize(DigitalBouquet $bouquet): array
    {
        return [
            'slug' => $bouquet->getSlug(),
            'title' => $bouquet->getTitle(),
            'stems' => $bouquet->getStemsJson(),
            'wrap_color' => $bouquet->getWrapColor()->value,
            'recipient_name' => $bouquet->getRecipientName(),
            'sender_name' => $bouquet->getSenderName(),
            'letter_body' => $bouquet->getLetterBody(),
            'letter_design' => $bouquet->getLetterDesign()->value,
            'content_json' => $bouquet->getContentJson(),
            'published_at' => $bouquet->getPublishedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
