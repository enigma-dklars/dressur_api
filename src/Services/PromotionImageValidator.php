<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PromotionImageValidator
{
    private const MAX_UPLOAD_BYTES = 1024 * 1024;
    private const RATIO_TOLERANCE = 0.02;

    /**
     * MIME types already accepted by the Promotion Affaire upload flows.
     *
     * @var array<string, string>
     */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Validate an uploaded Promotion Affaire image without decoding or rewriting it.
     *
     * getimagesize() reads the image header and is available without GD or Imagick.
     *
     * @return array{
     *     valid: bool,
     *     message?: string,
     *     mime?: string,
     *     extension?: string,
     *     width?: int,
     *     height?: int,
     *     ratio?: float
     * }
     */
    public function validate(mixed $file): array
    {
        if (!$file instanceof UploadedFile) {
            return $this->invalid('Veuillez fournir une image valide.');
        }

        if (!$file->isValid()) {
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return $this->invalid('L’image ne doit pas dépasser 1 Mo.');
            }

            return $this->invalid('Le téléchargement de l’image a échoué. Veuillez réessayer.');
        }

        $size = $file->getSize();
        if (!is_int($size) || $size <= 0) {
            return $this->invalid('La taille de l’image n’a pas pu être vérifiée.');
        }

        if ($size > self::MAX_UPLOAD_BYTES) {
            return $this->invalid('L’image ne doit pas dépasser 1 Mo.');
        }

        $path = $file->getRealPath();
        if ($path === false || !is_file($path) || !is_readable($path)) {
            return $this->invalid('Le fichier image est introuvable ou illisible.');
        }

        $imageInfo = @getimagesize($path);
        if ($imageInfo === false) {
            return $this->invalid('Le fichier fourni n’est pas une image lisible ou est corrompu.');
        }

        $mime = isset($imageInfo['mime']) ? (string) $imageInfo['mime'] : '';
        if (!isset(self::MIME_EXTENSIONS[$mime])) {
            return $this->invalid('Format d’image non supporté. Utilisez une image JPG, PNG, GIF ou WebP.');
        }

        $width = $imageInfo[0] ?? null;
        $height = $imageInfo[1] ?? null;
        if (!is_int($width) || !is_int($height) || $width < 1 || $height < 1) {
            return $this->invalid('Les dimensions de l’image n’ont pas pu être vérifiées.');
        }

        $ratio = $width / $height;
        if (!$this->isAcceptedRatio($ratio)) {
            return $this->invalid(sprintf(
                'Le ratio de l’image (%d × %d) doit être 1:1, 4:3 ou 3:4.',
                $width,
                $height
            ));
        }

        return [
            'valid'     => true,
            'mime'      => $mime,
            'extension' => self::MIME_EXTENSIONS[$mime],
            'width'     => $width,
            'height'    => $height,
            'ratio'     => $ratio,
        ];
    }

    private function isAcceptedRatio(float $ratio): bool
    {
        foreach ([1.0, 4 / 3, 3 / 4] as $acceptedRatio) {
            if (abs($ratio - $acceptedRatio) <= self::RATIO_TOLERANCE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{valid: false, message: string}
     */
    private function invalid(string $message): array
    {
        return [
            'valid'   => false,
            'message' => $message,
        ];
    }
}