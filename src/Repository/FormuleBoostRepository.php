<?php

namespace App\Repository;

use App\Entity\FormuleBoost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormuleBoost>
 *
 * @method FormuleBoost|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormuleBoost|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormuleBoost[]    findAll()
 * @method FormuleBoost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormuleBoostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormuleBoost::class);
    }

    public function add(FormuleBoost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormuleBoost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findReplacementForDeletion(FormuleBoost $formula): ?FormuleBoost
    {
        $candidates = $this->findBy([
            'activated' => true,
            'typeBoost' => $formula->getTypeBoost(),
        ], [
            'prix' => 'ASC',
            'id' => 'ASC',
        ]);

        $sourcePrice = (float) ($formula->getPrix() ?? 0);
        $eligibleCandidates = array_values(array_filter(
            $candidates,
            static function (FormuleBoost $candidate) use ($formula, $sourcePrice): bool {
                if ($candidate->getId() === $formula->getId()) {
                    return false;
                }

                return $sourcePrice <= 0
                    ? $candidate->getPrix() <= 0
                    : $candidate->getPrix() > 0;
            }
        ));

        if ($eligibleCandidates === []) {
            return null;
        }

        usort($eligibleCandidates, static function (FormuleBoost $left, FormuleBoost $right) use ($sourcePrice): int {
            $leftPrice = (float) ($left->getPrix() ?? 0);
            $rightPrice = (float) ($right->getPrix() ?? 0);
            $leftDistance = abs($leftPrice - $sourcePrice);
            $rightDistance = abs($rightPrice - $sourcePrice);

            if ($leftDistance !== $rightDistance) {
                return $leftDistance <=> $rightDistance;
            }

            if ($leftPrice !== $rightPrice) {
                return $leftPrice <=> $rightPrice;
            }

            return $left->getId() <=> $right->getId();
        });

        return $eligibleCandidates[0];
    }


}
