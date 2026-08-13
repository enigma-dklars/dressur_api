<?php

namespace App\Tests\Services;

use App\Entity\Promotion;
use App\Entity\Transaction;
use App\Services\TraitementsDS;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PromotionPaymentOptionsTest extends TestCase
{
    public function testBalancePaymentKeepsPromotionOptionsAndTransactionAmount(): void
    {
        $promotion = new Promotion();
        $transaction = (new Transaction())
            ->setAmount(1300)
            ->setAnnotherInfo([
                'inProgrammeRecompense' => true,
                'rewardBudget' => 500,
                'publishOnDressurStatus' => true,
                'boostFacebook' => true,
                'montantBoostFacebook' => 700,
                'source' => 'web',
                'whatsappContact' => '+22900000000',
            ]);
        $amountBeforeApplyingOptions = $transaction->getAmount();

        $traitementsDS = $this->createTraitementsDS();
        $traitementsDS->appliquerOptionsPromotionPaiement(
            $promotion,
            $transaction->getAnnotherInfo(),
            true
        );

        self::assertTrue($promotion->isBoostFacebook());
        self::assertSame(700, $promotion->getMontantBoostFacebook());
        self::assertSame(500, $promotion->getAnnotherInfo()['rewardBudget']);
        self::assertSame($amountBeforeApplyingOptions, $transaction->getAmount());
        self::assertSame(1300, $transaction->getAmount());
    }

    public function testReboostKeepsExistingPromotionInfoAndUpdatesFacebookOptions(): void
    {
        $promotion = (new Promotion())
            ->setAnnotherInfo(['existingOption' => 'keep']);

        $traitementsDS = $this->createTraitementsDS();
        $traitementsDS->appliquerOptionsPromotionPaiement($promotion, [
            'inProgrammeRecompense' => true,
            'rewardBudget' => 1000,
            'publishOnDressurStatus' => false,
            'boostFacebook' => true,
            'montantBoostFacebook' => 900,
            'source' => 'mobile',
        ]);

        self::assertTrue($promotion->isBoostFacebook());
        self::assertSame(900, $promotion->getMontantBoostFacebook());
        self::assertSame(1000, $promotion->getAnnotherInfo()['rewardBudget']);
        self::assertSame('keep', $promotion->getAnnotherInfo()['existingOption']);
    }

    private function createTraitementsDS(): TraitementsDS
    {
        /** @var TraitementsDS $traitementsDS */
        $traitementsDS = (new ReflectionClass(TraitementsDS::class))->newInstanceWithoutConstructor();

        return $traitementsDS;
    }
}