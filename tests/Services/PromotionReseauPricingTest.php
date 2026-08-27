<?php

namespace App\Tests\Services;

use App\Entity\FormulePromoReseau;
use App\Services\PromotionReseauPricing;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PromotionReseauPricingTest extends TestCase
{
    public function testAmountUsesFormulaReferencePriceQuantityAndInterfaceRounding(): void
    {
        $formule = $this->formule(1.0, 100, 10, 1000);

        // round(1 * 1.2 * 1.7 * 700) + 1 = 1429, then round(1429 * 50 / 100) = 715.
        self::assertSame(715, (new PromotionReseauPricing())->calculateAmount($formule, 50));
    }

    public function testPriceUses1000UnitReferenceForProportionalQuantities(): void
    {
        $formule = $this->formule(1.0, 1000, 10, 5000);
        $pricing = new PromotionReseauPricing();

        self::assertSame(1429, $pricing->calculateAmount($formule, 1000));
        self::assertSame(715, $pricing->calculateAmount($formule, 500));
        self::assertSame(2858, $pricing->calculateAmount($formule, 2000));
    }

    public function testVendorReferencePriceMatchesTheFormulaList(): void
    {
        $formule = $this->formule(2.0, 100, 10, 1000);
        $formule->setPrixVendeur(1.0);

        self::assertSame(1429, (new PromotionReseauPricing())->calculateAmount($formule, 100, true));
        self::assertSame(2857, (new PromotionReseauPricing())->calculateAmount($formule, 100, false));
    }

    /**
     * @dataProvider invalidQuantityProvider
     */
    public function testQuantityMustStayWithinFormulaBounds(int $quantity): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PromotionReseauPricing())->calculateAmount(
            $this->formule(1.0, 100, 10, 1000),
            $quantity
        );
    }

    public function testClientProvidedAmountCanOnlyMatchTheTrustedAmount(): void
    {
        $pricing = new PromotionReseauPricing();

        self::assertTrue($pricing->clientAmountMatches(null, 715));
        self::assertTrue($pricing->clientAmountMatches('715', 715));
        self::assertFalse($pricing->clientAmountMatches('999999', 715));
        self::assertFalse($pricing->clientAmountMatches('715.00', 715));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidQuantityProvider(): iterable
    {
        yield 'below minimum' => [9];
        yield 'above maximum' => [1001];
    }

    private function formule(float $price, int $quantity, int $minimum, int $maximum): FormulePromoReseau
    {
        return (new FormulePromoReseau())
            ->setTitre('Formule test')
            ->setPrix($price)
            ->setQte($quantity)
            ->setQteMin($minimum)
            ->setQteMax($maximum)
            ->setAvailable(true);
    }
}