<?php

namespace App\Tests\Services;

use App\Services\PromotionImageValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PromotionImageValidatorTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $temporaryFiles = [];

    /**
     * @dataProvider acceptedRatioProvider
     */
    public function testAcceptsSupportedPromotionImageRatios(int $width, int $height, float $expectedRatio): void
    {
        $result = $this->validator()->validate(
            $this->uploadedFile($this->png($width, $height), "{$width}x{$height}.png")
        );

        self::assertTrue($result['valid']);
        self::assertSame('image/png', $result['mime']);
        self::assertSame($width, $result['width']);
        self::assertSame($height, $result['height']);
        self::assertEqualsWithDelta($expectedRatio, $result['ratio'], 0.0001);
    }

    /**
     * @return array<string, array{int, int, float}>
     */
    public static function acceptedRatioProvider(): array
    {
        return [
            'square 1:1' => [4, 4, 1.0],
            'landscape 4:3' => [4, 3, 4 / 3],
            'portrait 3:4' => [3, 4, 3 / 4],
        ];
    }

    public function testRejectsAnUnsupportedRatio(): void
    {
        $result = $this->validator()->validate(
            $this->uploadedFile($this->png(5, 3), 'unsupported-ratio.png')
        );

        self::assertFalse($result['valid']);
        self::assertSame(
            'Le ratio de l’image (5 × 3) doit être 1:1, 4:3 ou 3:4.',
            $result['message']
        );
    }

    public function testRejectsAFileThatIsNotAnImage(): void
    {
        $result = $this->validator()->validate(
            $this->uploadedFile('ceci n’est pas une image', 'not-an-image.txt', 'text/plain')
        );

        self::assertFalse($result['valid']);
        self::assertSame(
            'Le fichier fourni n’est pas une image lisible ou est corrompu.',
            $result['message']
        );
    }

    public function testRejectsACorruptedImage(): void
    {
        $result = $this->validator()->validate(
            $this->uploadedFile("\x89PNG\r\n\x1a\n\x00\x00\x00\x0dIHDR", 'corrupted.png')
        );

        self::assertFalse($result['valid']);
        self::assertSame(
            'Le fichier fourni n’est pas une image lisible ou est corrompu.',
            $result['message']
        );
    }

    public function testRejectsAnImageWhoseDimensionsCannotBeVerified(): void
    {
        $validator = new PromotionImageValidator(
            static function (string $path): array {
                return [
                    0 => null,
                    1 => null,
                    'mime' => 'image/png',
                ];
            }
        );

        $result = $validator->validate(
            $this->uploadedFile($this->png(1, 1), 'unknown-dimensions.png')
        );

        self::assertFalse($result['valid']);
        self::assertSame(
            'Les dimensions de l’image n’ont pas pu être vérifiées.',
            $result['message']
        );
    }

    public function testMobileApiReturnsTheValidatorMessageInItsErrorPayload(): void
    {
        $controller = $this->source('src/Controller/API/PromotionController.php');

        self::assertStringContainsString(
            "'titre' => 'Image invalide'",
            $controller
        );
        self::assertStringContainsString(
            "'message' => \$imageValidation['message']",
            $controller
        );
        self::assertStringContainsString(
            'Response::HTTP_BAD_REQUEST',
            $controller
        );
    }

    public function testWebReturnsTheValidatorMessageInItsValidationErrors(): void
    {
        $controller = $this->source('src/Controller/Crud/CrudPromotionController.php');

        self::assertStringContainsString(
            '$errors[] = $imageValidation[\'message\'];',
            $controller
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->temporaryFiles = [];

        parent::tearDown();
    }

    private function validator(): PromotionImageValidator
    {
        return new PromotionImageValidator();
    }

    private function uploadedFile(string $contents, string $name, string $mime = 'image/png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'promotion-image-');
        self::assertNotFalse($path);
        self::assertNotFalse(file_put_contents($path, $contents));
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, $mime, UPLOAD_ERR_OK, true);
    }

    private function png(int $width, int $height): string
    {
        $chunk = static function (string $type, string $payload): string {
            return pack('N', strlen($payload))
                . $type
                . $payload
                . pack('N', crc32($type . $payload));
        };

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('N2CCCCC', $width, $height, 8, 6, 0, 0, 0))
            . $chunk('IEND', '');
    }

    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $contents = file_get_contents($path);

        self::assertNotFalse($contents);

        return $contents;
    }
}