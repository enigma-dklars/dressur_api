<?php

namespace App\Repository;

use App\Entity\FormulePromoReseau;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormulePromoReseau>
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
        if ($flush) { $this->getEntityManager()->flush(); }
    }

    public function remove(FormulePromoReseau $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) { $this->getEntityManager()->flush(); }
    }

    /** Plateformes parentes ayant au moins un enfant disponible (pour sitemap / pages). */
    public function findAvailableParents(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.sonFormulePromoReseaus', 'c')
            ->where('f.parent IS NULL')
            ->andWhere('c.available = true')
            ->groupBy('f.id')
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Tous les services enfants disponibles (pour sitemap). */
    public function findAllAvailableChildren(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.parent IS NOT NULL')
            ->andWhere('f.available = true')
            ->orderBy('f.parent', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Formules disponibles avec un prix non nul.
     * Remplace findAll() + array_filter(getPrix > 0 && isAvailable) en PHP
     * sur /promotion-reseaux-sociaux et /services-all.
     *
     * @return FormulePromoReseau[]
     */
    public function findAvailableWithPrice(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.available = true')
            ->andWhere('f.prix > 0')
            ->getQuery()
            ->getResult();
    }
}
