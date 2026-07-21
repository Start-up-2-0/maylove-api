<?php

declare(strict_types=1);

namespace App\Application\Bouquet;

use App\Infrastructure\Persistence\Entity\DigitalBouquet;

final class DigitalBouquetSerializer
{
    /** @return array<string, mixed> */
    public function serializeSummary(DigitalBouquet $bouquet): array
    {
        return [
            'id' => (string) $bouquet->getId(),
            'slug' => $bouquet->getSlug(),
            'status' => $bouquet->getStatus()->value,
            'title' => $bouquet->getTitle(),
            'stems_count' => count($bouquet->getStemsJson()),
            'wrap_color' => $bouquet->getWrapColor()->value,
            'recipient_name' => $bouquet->getRecipientName(),
            'sender_name' => $bouquet->getSenderName(),
            'letter_design' => $bouquet->getLetterDesign()->value,
            'views_count' => $bouquet->getViewsCount(),
            'published_at' => $bouquet->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $bouquet->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $bouquet->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function serializeDetail(DigitalBouquet $bouquet): array
    {
        $data = $this->serializeSummary($bouquet);
        $data['stems'] = $bouquet->getStemsJson();
        $data['letter_body'] = $bouquet->getLetterBody();
        $data['content_json'] = $bouquet->getContentJson();

        return $data;
    }
}
