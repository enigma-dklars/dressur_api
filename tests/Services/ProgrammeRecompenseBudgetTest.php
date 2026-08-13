<?php

namespace App\Tests\Services;

use App\Services\ProgrammeRecompenseBudget;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProgrammeRecompenseBudgetTest extends TestCase
{
    /**
     * @dataProvider acceptedBudgetProvider
     */
    public function testAcceptedBudgets(mixed $rawBudget, int $expectedBudget): void
    {
        $resolver = new ProgrammeRecompenseBudget();

        self::assertSame($expectedBudget, $resolver->validate($rawBudget));
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function acceptedBudgetProvider(): iterable
    {
        yield '500' => ['500', 500];
        yield '1000' => [1000, 1000];
        yield '2000' => ['2000', 2000];
        yield '5000' => ['5000', 5000];
        yield 'custom 5001' => ['5001', 5001];
        yield 'custom 10000' => [10000, 10000];
    }

    /**
     * @dataProvider rejectedBudgetProvider
     */
    public function testRejectedBudgets(mixed $rawBudget): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProgrammeRecompenseBudget())->validate($rawBudget);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectedBudgetProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => ['-500'];
        yield 'decimal string' => ['500.5'];
        yield 'decimal float' => [500.0];
        yield 'empty string' => [''];
        yield 'text' => ['five hundred'];
    }

    public function testPredefinedAmountCannotBeMarkedAsCustom(): void
    {
        $this->expectExceptionMessage('strictement supérieur à 5 000 FCFA');

        (new ProgrammeRecompenseBudget())->validate('5000', true);
    }

    public function testDisabledProgrammeIgnoresInvalidBudget(): void
    {
        $result = (new ProgrammeRecompenseBudget())->resolve(
            false,
            true,
            'not-a-number',
            true,
            '4000'
        );

        self::assertSame(['amount' => 0, 'legacy' => false], $result);
    }

    public function testLegacyCalculationOnlyRunsWhenBudgetIsAbsent(): void
    {
        $resolver = new ProgrammeRecompenseBudget();

        $legacy = $resolver->resolve(true, false, null, false, '4000');
        self::assertSame(['amount' => 3000, 'legacy' => true], $legacy);

        $this->expectException(InvalidArgumentException::class);
        $resolver->resolve(true, true, 'invalid', false, '4000');
    }
}