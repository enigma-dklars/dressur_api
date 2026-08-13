<?php

namespace App\Services;

final class PromotionBilling
{
    /**
     * Calculate the amount that must be used consistently by every payment path.
     *
     * The client-provided totalAmount is intentionally not accepted here. The API
     * must derive the amount from trusted formula and option values.
     */
    public function calculateTotal(
        int $formulaAmount,
        bool $rewardEnabled = false,
        int $rewardBudget = 0,
        bool $publishOnDressurStatus = false,
        int $formulaDays = 0,
        bool $facebookEnabled = false,
        int $facebookAmount = 0
    ): int {
        $total = $formulaAmount;

        if ($rewardEnabled) {
            $total += $rewardBudget;
        }

        if ($publishOnDressurStatus) {
            $total += (int) round(($formulaDays * 5000) / 7);
        }

        if ($facebookEnabled) {
            $total += $facebookAmount;
        }

        return $total;
    }
}