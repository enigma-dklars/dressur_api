<?php

namespace App\Repository;

use App\Entity\FormulePromoReseau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormulePromoReseau>
 *
 * @method FormulePromoReseau|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormulePromoReseau|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormulePromoReseau[]    findAll()
 * @method FormulePromoReseau[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormulePromoReseauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormulePromoReseau::class);
    }

    public function save(FormulePromoReseau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FormulePromoReseau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne toutes les formules parentes (plateformes) ayant au moins
     * un enfant disponible — utilisé pour le sitemap.
     *
     * @return FormulePromoReseau[]
     */
    public function findAvailableParents(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.parent IS NULL')
            ->andWhere(
                $this->getEntityManager()->createQueryBuilder()
                    ->select('1')
                    ->from(FormulePromoReseau::class, 'c')
                    ->where('c.parent = f')
                    ->andWhere('c.available = true')
                    ->getDQL()
                    . ' IS NOT NULL'
            )
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
