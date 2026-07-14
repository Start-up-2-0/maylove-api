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
        // Catálogo público: Memory Book + murais (Polaroid / Retrato).
        $canonical = [
            BookPresentation::ClassicPhotobook,
            BookPresentation::PolaroidBoard,
            BookPresentation::PortraitAlbum,
            BookPresentation::InstantPhoto,
        ];

        return array_map(
            fn (BookPresentation $presentation) => [
                'id' => $presentation->value,
                'name' => $presentation->label(),
                'description' => $this->descriptionFor($presentation),
            ],
            $canonical,
        );
    }

    private function descriptionFor(BookPresentation $presentation): string
    {
        return match ($presentation) {
            BookPresentation::ClassicPhotobook => 'Livro editorial personalizável: capa, cores e páginas sob seu controle.',
            BookPresentation::WeddingBook => 'Romântico e sofisticado. Composições assimétricas para histórias de casamento.',
            BookPresentation::FamilyMemories => 'Álbum de família acolhedor. Páginas quentes com espaço para relatos e datas.',
            BookPresentation::PolaroidMemories => 'Molduras instantâneas com anotações manuscritas na margem branca.',
            BookPresentation::PolaroidBoard => 'Quadro com polaroids coladas, fitas e pins — um mural de memórias.',
            BookPresentation::PortraitAlbum => 'Página de álbum retrato: papel envelhecido, cantos e fotos com borda recortada.',
            BookPresentation::InstantPhoto => 'Colagem só de fotos com moldura instantânea — ideal para família, casamento, aniversário e formatura.',
            BookPresentation::Scrapbook => 'Papéis texturizados, fitas e elementos artesanais com charme handmade.',
            BookPresentation::TravelJournal => 'Diário de viagem editorial. Datas e relatos por destino.',
            BookPresentation::MagazineStyle => 'Layout editorial ousado. Tipografia forte e fotos em múltiplas proporções.',
            BookPresentation::LuxuryAlbum => 'Acabamento premium. Imagens grandes, pouco texto e muito espaço em branco.',
            BookPresentation::Timeline => 'Momentos em ordem cronológica com rolagem vertical contínua.',
            BookPresentation::FamilyAlbum => 'Álbum de família acolhedor. Páginas quentes com espaço para relatos e datas.',
            BookPresentation::Polaroid => 'Molduras instantâneas com anotações manuscritas na margem branca.',
            BookPresentation::MemoryNotebook => 'Diário de viagem editorial. Datas e relatos por destino.',
            BookPresentation::RomanticBook => 'Romântico e sofisticado. Composições assimétricas para histórias de casamento.',
            BookPresentation::PhotoMagazine => 'Layout editorial ousado. Tipografia forte e fotos em múltiplas proporções.',
        };
    }
}
