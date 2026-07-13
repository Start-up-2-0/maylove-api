<?php

declare(strict_types=1);

namespace App\Application\Media;

final class MediaUploadPolicy
{
  public const PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
  public const AUDIO_MIMES = ['audio/mpeg', 'audio/mp3'];

  public const MAX_PHOTO_UPLOAD_BYTES = 15 * 1024 * 1024;
  public const MAX_AUDIO_UPLOAD_BYTES = 10 * 1024 * 1024;
  public const MAX_PHOTOS_PER_ALBUM = 40;
  public const MAX_IMAGE_LONG_EDGE = 2560;

  /**
   * @return array<string, mixed>
   */
  public function albumPolicy(): array
  {
    return [
      'photo' => [
        'accepted_mimes' => self::PHOTO_MIMES,
        'accepted_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_file_bytes' => self::MAX_PHOTO_UPLOAD_BYTES,
        'max_long_edge_px' => self::MAX_IMAGE_LONG_EDGE,
        'max_count_per_album' => self::MAX_PHOTOS_PER_ALBUM,
        'output_format_hint' => 'WebP (otimizado automaticamente após o envio)',
      ],
      'audio' => [
        'accepted_mimes' => self::AUDIO_MIMES,
        'accepted_extensions' => ['mp3', 'mpeg'],
        'max_file_bytes' => self::MAX_AUDIO_UPLOAD_BYTES,
        'max_count_per_album' => 1,
        'output_format_hint' => 'MP3 128 kbps (otimizado automaticamente após o envio)',
      ],
    ];
  }
}
