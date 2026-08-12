<?php

namespace App\Tests\Services;

use App\Services\PromotionBilling;
use PHPUnit\Framework\TestCase;

final class PromotionBillingTest extends TestCase
{
    /**
     * @dataProvider billingScenarioProvider
     */
    public function testEveryPaymentPathUsesTheSameRecalculatedAmount(
        string $scenario,
        int $formulaAmount,
        bool $rewardEnabled,
        int $rewardBudget,
        bool $facebookEnabled,
        int $facebookAmount,
        int $expectedAmount,
        int $expectedRecordedRewardBudget
    ): void {
        $billing = new PromotionBilling();
        $recalculatedAmount = $billing->calculateTotal(
            $formulaAmount,
            $rewardEnabled,
            $rewardBudget,
            false,
            0,
            $facebookEnabled,
            $facebookAmount
        );

        $gateways = [
            'FedaPay' => new MockPaymentGateway(),
            'KPay' => new MockPaymentGateway(),
            'FeexPay' => new MockPaymentGateway(),
        ];
        foreach ($gateways as $gateway) {
            $gateway->createPayment($recalculatedAmount);
        }
        $balanceBefore = 10_000;
        $balanceAfter = $balanceBefore - $recalculatedAmount;
        $transaction = [
            'amount' => $recalculatedAmount,
            'annotherInfo' => [
                'rewardBudget' => $expectedRecordedRewardBudget,
            ],
        ];

        self::assertSame($expectedAmount, $recalculatedAmount, $scenario);
        self::assertSame($expectedAmount, $recalculatedAmount, 'Displayed amount');
        self::assertSame($expectedAmount, $gateways['FedaPay']->amount, 'FedaPay amount');
        self::assertSame($expectedAmount, $gateways['KPay']->amount, 'KPay amount');
        self::assertSame($expectedAmount, $gateways['FeexPay']->amount, 'FeexPay amount');
        self::assertSame($expectedAmount, $balanceBefore - $balanceAfter, 'Balance debit');
        self::assertSame($expectedAmount, $transaction['amount'], 'Transaction amount');
        self::assertSame(
            $expectedRecordedRewardBudget,
            $transaction['annotherInfo']['rewardBudget'],
            'Recorded rewardBudget'
        );
    }

    public function testClientTotalAmountCannotChangeTheApiAmount(): void
    {
        $billing = new PromotionBilling();
        $trustedAmount = $billing->calculateTotal(
            100,
            true,
            500,
            false,
            0,
            true,
            700
        );
        $falsifiedClientAmount = 99_999;

        self::assertSame(1300, $trustedAmount);
        self::assertNotSame($falsifiedClientAmount, $trustedAmount);
    }

    /**
     * @return iterable<string, array{string, int, bool, int, bool, int, int, int}>
     */
    public static function billingScenarioProvider(): iterable
    {
        yield 'formula only' => ['formula 100 only', 100, false, 0, false, 0, 100, 0];
        yield 'formula plus reward 500' => ['formula 100 + reward 500', 100, true, 500, false, 0, 600, 500];
        yield 'formula plus Facebook 700' => ['formula 100 + Facebook 700', 100, false, 0, true, 700, 800, 0];
        yield 'formula plus Facebook and reward' => ['formula 100 + Facebook 700 + reward 500', 100, true, 500, true, 700, 1300, 500];
        yield 'formula plus reward 1000' => ['formula 100 + reward 1000', 100, true, 1000, false, 0, 1100, 1000];
        yield 'formula plus custom reward 5001' => ['formula 100 + custom reward 5001', 100, true, 5001, false, 0, 5101, 5001];
        yield 'disabled programme ignores reward budget' => ['disabled programme with rewardBudget', 100, false, 500, false, 0, 100, 0];
        yield 'web reboost' => ['web reboost 100 + Facebook 700 + reward 500', 100, true, 500, true, 700, 1300, 500];
        yield 'mobile reboost' => ['mobile reboost 100 + Facebook 700 + reward 500', 100, true, 500, true, 700, 1300, 500];
    }
}

/**
 * In-memory gateway double: no SDK or network call is made by these tests.
 */
final class MockPaymentGateway
{
    public ?int $amount = null;

    public function createPayment(int $amount): void
    {
        $this->amount = $amount;
    }
}