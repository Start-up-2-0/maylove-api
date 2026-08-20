<?php

declare(strict_types=1);

namespace App\Application\Public;

final class PublicTributeContentFilter
{
    private const ALLOWED_KEYS = [
        'style_id', 'presentation', 'font', 'background', 'effects',
        'animation_speed', 'entrance', 'photo_style', 'text_style',
        'question', 'celebration', 'section_order',
        'special_date_config', 'timeline', 'messages', 'event_info',
        'sender_name', 'signature', 'video_url', 'modules',
        'music_autoplay', 'music_loop', 'music_start_seconds',
        'music_end_seconds', 'music_duration_seconds',
        'include_opening_message', 'include_closing_message',
        'romance_theme_id', 'retrospective_palette_id', 'romance_experience_id',
    ];

    /**
     * @param array<string, mixed>|null $content
     *
     * @return array<string, mixed>
     */
    public function filter(?array $content): array
    {
        if ($content === null) {
            return [];
        }

        return array_intersect_key($content, array_flip(self::ALLOWED_KEYS));
    }
}
