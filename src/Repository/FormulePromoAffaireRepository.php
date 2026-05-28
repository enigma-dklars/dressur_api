<?php

namespace App\Repository;

use App\Entity\FormulePromoAffaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormulePromoAffaire>
 *
 * @method FormulePromoAffaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormulePromoAffaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormulePromoAffaire[]    findAll()
 * @method FormulePromoAffaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormulePromoAffaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormulePromoAffaire::class);
    }


}
