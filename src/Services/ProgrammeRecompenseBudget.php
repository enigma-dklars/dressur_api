<?php

namespace App\Services;

use InvalidArgumentException;

final class ProgrammeRecompenseBudget
{
    /**
     * @var list<int>
     */
    public const PREDEFINED_AMOUNTS = [500, 1000, 2000, 5000];

    /**
     * @return array{amount:int, legacy:bool}
     */
    public function resolve(
        bool $enabled,
        bool $hasRewardBudget,
        mixed $rawRewardBudget,
        bool $isCustom,
        mixed $rawTotalViewsGoal = null
    ): array {
        if (!$enabled) {
            return ['amount' => 0, 'legacy' => false];
        }

        if (!$hasRewardBudget) {
            return [
                'amount' => $this->calculateLegacyAmount($rawTotalViewsGoal),
                'legacy' => true,
            ];
        }

        return [
            'amount' => $this->validate($rawRewardBudget, $isCustom),
            'legacy' => false,
        ];
    }

    public function validate(mixed $rawRewardBudget, bool $isCustom = false): int
    {
        $rewardBudget = $this->strictInteger($rawRewardBudget);

        if ($rewardBudget === null || $rewardBudget <= 0) {
            throw new InvalidArgumentException(
                'Le champ rewardBudget doit être un entier positif en FCFA.'
            );
        }

        if ($isCustom && $rewardBudget <= 5000) {
            throw new InvalidArgumentException(
                'Un montant personnalisé doit être strictement supérieur à 5 000 FCFA.'
            );
        }

        if ($rewardBudget <= 5000 && !in_array($rewardBudget, self::PREDEFINED_AMOUNTS, true)) {
            throw new InvalidArgumentException(
                'Le rewardBudget doit être 500, 1 000, 2 000, 5 000 FCFA ou supérieur à 5 000 FCFA.'
            );
        }

        return $rewardBudget;
    }

    private function calculateLegacyAmount(mixed $rawTotalViewsGoal): int
    {
        $totalViewsGoal = $rawTotalViewsGoal === null || $rawTotalViewsGoal === ''
            ? 2500
            : (int) $rawTotalViewsGoal;

        return (int) round((($totalViewsGoal * 2500) / 4000) * 1.20);
    }

    private function strictInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value) || !preg_match('/^\d+$/D', $value)) {
            return null;
        }

        return (int) $value;
    }
}