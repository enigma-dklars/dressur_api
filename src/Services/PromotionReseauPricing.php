<?php

namespace App\Services;

use App\Entity\FormulePromoReseau;
use InvalidArgumentException;

final class PromotionReseauPricing
{
    private const PRICE_MULTIPLIER = 1.2 * 1.7 * 700;

    /**
     * Reproduit le prix affiché par listeFormulePromoReseau et le prorata
     * calculé par les interfaces, sans utiliser de montant fourni par le client.
     */
    public function calculateAmount(
        FormulePromoReseau $formule,
        int $quantity,
        bool $isVendeur = false
    ): int {
        $referenceQuantity = $formule->getQte();
        $minimumQuantity = $formule->getQteMin();
        $maximumQuantity = $formule->getQteMax();
        $referencePrice = $isVendeur && $formule->getPrixVendeur() !== null
            ? $formule->getPrixVendeur()
            : $formule->getPrix();

        if (
            $referencePrice === null
            || $referencePrice <= 0
            || $referenceQuantity === null
            || $referenceQuantity <= 0
            || $minimumQuantity === null
            || $maximumQuantity === null
            || $minimumQuantity < 1
            || $maximumQuantity < $minimumQuantity
        ) {
            throw new InvalidArgumentException('La formule de promotion est indisponible ou mal configurée.');
        }

        if ($quantity < $minimumQuantity || $quantity > $maximumQuantity) {
            throw new InvalidArgumentException(sprintf(
                'La quantité demandée doit être comprise entre %d et %d.',
                $minimumQuantity,
                $maximumQuantity
            ));
        }

        $referenceAmount = (int) round($referencePrice * self::PRICE_MULTIPLIER) + 1;

        return (int) round(
            ($referenceAmount * $quantity) / $referenceQuantity,
            0,
            PHP_ROUND_HALF_UP
        );
    }

    /**
     * Le montant client est facultatif et n'est accepté que s'il correspond
     * exactement au montant recalculé côté serveur.
     */
    public function clientAmountMatches(mixed $clientAmount, int $trustedAmount): bool
    {
        if ($clientAmount === null) {
            return true;
        }
        if (!is_scalar($clientAmount)) {
            return false;
        }

        $normalized = trim((string) $clientAmount);
        if ($normalized === '') {
            return true;
        }

        return preg_match('/^\d+$/', $normalized) === 1
            && (int) $normalized === $trustedAmount;
    }
}