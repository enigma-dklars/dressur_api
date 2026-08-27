<?php

declare(strict_types=1);

namespace App\Tests\Utilities;

use App\Entity\Env;
use App\Repository\EnvRepository;
use App\Utilities\ZefameApi;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ZefameApiTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['ZEFAME_API_KEY'], $_SERVER['ZEFAME_API_KEY']);
        putenv('ZEFAME_API_KEY');
        parent::tearDown();
    }

    public function testEntityKeyHasPriorityOverEnvironmentFallback(): void
    {
        /** @var EnvRepository&MockObject $repository */
        $repository = $this->createMock(EnvRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn((new Env())->setZefameApiKey('  key-from-env-entity  '));
        $_ENV['ZEFAME_API_KEY'] = 'key-from-process';

        $api = new ZefameApi($repository);

        self::assertSame('key-from-env-entity', $this->readApiKey($api));
    }

    public function testEnvironmentKeyIsUsedWhenEntityDoesNotHaveOne(): void
    {
        /** @var EnvRepository&MockObject $repository */
        $repository = $this->createMock(EnvRepository::class);
        $repository->expects(self::once())->method('find')->with(1)->willReturn(new Env());
        $_ENV['ZEFAME_API_KEY'] = '  key-from-process  ';

        $api = new ZefameApi($repository);

        self::assertSame('key-from-process', $this->readApiKey($api));
    }

    public function testDirectInstantiationRemainsSafeWhenNoConfigurationExists(): void
    {
        $api = new ZefameApi();

        self::assertSame('', $this->readApiKey($api));
    }

    private function readApiKey(ZefameApi $api): string
    {
        $property = new ReflectionProperty($api, 'api_key');
        $property->setAccessible(true);

        return (string)$property->getValue($api);
    }
}
