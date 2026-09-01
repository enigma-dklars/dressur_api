<?php

namespace App\Services;

use App\Entity\Boost;
use App\Entity\User;
use App\Repository\BoostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AdminBoostContactAssistanceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private BoostRepository $boostRepository,
    ) {
    }

    /**
     * Retourne des utilisateurs correspondant aux préférences des deux côtés,
     * sans appliquer les restrictions normales de permission d'ajout.
     *
     * @return User[]
     */
    public function findAvailableCandidates(Boost $boost): array
    {
        $owner = $boost->getUser();
        if (!$owner || !$owner->getPreference() || !$owner->getContact()) {
            return [];
        }

        $ownerCountries = array_map('strval', $owner->getPreference()->getPaysChoisies());
        $ownerCountry = $owner->getPays();
        $alreadyAdded = array_unique(array_merge(
            $owner->getContact()->getWhoIAdd(),
            $owner->getContact()->getWhoAddMe()
        ));

        $candidates = [];
        foreach ($this->userRepository->findAll() as $candidate) {
            if (!$candidate instanceof User || $candidate->getId() === $owner->getId()) {
                continue;
            }
            if ($candidate->getAdmin() === true || $candidate->getBlocked() === true || !$candidate->getPreference() || !$candidate->getContact()) {
                continue;
            }
            if (in_array($candidate->getId(), $alreadyAdded, true)) {
                continue;
            }
            if (!in_array((string) $candidate->getPays(), $ownerCountries, true)) {
                continue;
            }

            $candidateCountries = array_map('strval', $candidate->getPreference()->getPaysChoisies());
            if (!in_array((string) $ownerCountry, $candidateCountries, true)) {
                continue;
            }

            $candidates[] = $candidate;
        }

        shuffle($candidates);
        return $candidates;
    }

    /**
     * Ajoute jusqu'à $quantity candidats et met à jour les compteurs des Boosts.
     * Les notifications utilisateurs ne sont volontairement pas créées.
     *
     * @return array{requested:int, added:int, remaining:int}
     */
    public function addContacts(Boost $ownerBoost, int $quantity): array
    {
        $quantity = max(0, $quantity);
        $owner = $ownerBoost->getUser();
        if (!$owner || !$owner->getContact() || $quantity === 0) {
            return ['requested' => $quantity, 'added' => 0, 'remaining' => 0];
        }

        $candidates = $this->findAvailableCandidates($ownerBoost);
        $selected = array_slice($candidates, 0, $quantity);
        $added = 0;

        $this->entityManager->wrapInTransaction(function () use ($owner, $ownerBoost, $selected, &$added): void {
            foreach ($selected as $candidate) {
                $ownerContact = $owner->getContact();
                $candidateContact = $candidate->getContact();
                if (!$ownerContact || !$candidateContact) {
                    continue;
                }

                // Les méthodes de l'entité évitent les doublons dans chaque sens.
                $ownerContact->setNewIAdd($candidate);
                $candidateContact->setNewAddMe($owner);
                $added++;

                $this->incrementBoost($this->boostRepository->findOldestActiveBoostForUser($candidate));
            }

            // Le Boost choisi est toujours celui du propriétaire de l'opération.
            $ownerBoost->setNbContactsObtenus($ownerBoost->getNbContactsObtenus() + $added);
            $this->closeQuotaIfReached($ownerBoost);
            $this->entityManager->flush();
        });

        return [
            'requested' => $quantity,
            'added' => $added,
            'remaining' => max(0, $quantity - $added),
        ];
    }

    private function incrementBoost(?Boost $boost): void
    {
        if (!$boost) {
            return;
        }

        $boost->setNbContactsObtenus($boost->getNbContactsObtenus() + 1);
        $this->closeQuotaIfReached($boost);
    }

    private function closeQuotaIfReached(Boost $boost): void
    {
        if ($boost->getTypeBoost() !== 'quota' || $boost->getDateExp() !== null) {
            return;
        }

        $maximum = $boost->getFormuleBoost()?->getNbContactsMax();
        if ($maximum !== null && $boost->getNbContactsObtenus() >= $maximum) {
            $boost->setDateExp(new \DateTime());
        }
    }
}

