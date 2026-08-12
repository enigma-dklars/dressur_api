<?php

namespace App\Tests\Services;

use App\Entity\Promotion;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class PromotionRewardBudgetDisplayTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = new Environment(
            new FilesystemLoader(dirname(__DIR__, 2) . '/templates')
        );
    }

    public function testDisabledProgrammeDisplaysNo(): void
    {
        $html = $this->renderPromotion(new Promotion());

        self::assertStringContainsString('<span class="badge bg-danger">No</span>', $html);
    }

    /**
     * @dataProvider rewardBudgetProvider
     */
    public function testPaidRewardBudgetDisplaysItsExactAmount(int $rewardBudget, string $expectedLabel): void
    {
        $promotion = (new Promotion())
            ->setInProgrammeRecompense(true)
            ->setAnnotherInfo(['rewardBudget' => $rewardBudget]);

        $html = $this->renderPromotion($promotion);

        self::assertStringContainsString(
            '<span class="badge bg-success">' . $expectedLabel . ' F</span>',
            $html
        );
    }

    public function testLegacyPromotionWithoutRewardBudgetDisplaysUnknownAmount(): void
    {
        $promotion = (new Promotion())
            ->setInProgrammeRecompense(true)
            ->setAnnotherInfo(['legacy' => true]);

        $html = $this->renderPromotion($promotion);

        self::assertStringContainsString('<span class="badge bg-secondary">—</span>', $html);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function rewardBudgetProvider(): iterable
    {
        yield '500 FCFA' => [500, '500'];
        yield '1 000 FCFA' => [1000, '1 000'];
        yield '2 000 FCFA' => [2000, '2 000'];
        yield '5 000 FCFA' => [5000, '5 000'];
        yield 'custom 5 001 FCFA' => [5001, '5 001'];
    }

    private function renderPromotion(Promotion $promotion): string
    {
        return $this->twig->render('crud_promotion/_reward_budget.html.twig', [
            'promotion' => $promotion,
        ]);
    }
}