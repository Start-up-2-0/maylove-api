<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\BookPresentation;

final class BookPresentationCatalogService
{
    /**
     * @return list<array<string, string>>
     */
    public function list(): array
    {
        return array_map(
            fn (BookPresentation $presentation) => [
                'id' => $presentation->value,
                'name' => $presentation->label(),
                'description' => $this->descriptionFor($presentation),
            ],
            BookPresentation::cases(),
        );
    }

    private function descriptionFor(BookPresentation $presentation): string
    {
        return match ($presentation) {
            BookPresentation::FamilyAlbum => 'Páginas afetivas com fotos coladas e anotações manuscritas.',
            BookPresentation::Polaroid => 'Fotos em molduras Polaroid com textos ao lado.',
            BookPresentation::MemoryNotebook => 'Diário ilustrado com papel texturizado e escrita à mão.',
            BookPresentation::RomanticBook => 'Design elegante e minimalista para histórias de amor.',
            BookPresentation::Timeline => 'Organização cronológica da sua história.',
            BookPresentation::PhotoMagazine => 'Layout editorial moderno com fotos em destaque.',
        };
    }
}
