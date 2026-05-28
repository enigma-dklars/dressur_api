<?php

namespace App\Repository;

use App\Entity\MethodePaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MethodePaiement>
 *
 * @method MethodePaiement|null find($id, $lockMode = null, $lockVersion = null)
 * @method MethodePaiement|null findOneBy(array $criteria, array $orderBy = null)
 * @method MethodePaiement[]    findAll()
 * @method MethodePaiement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MethodePaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MethodePaiement::class);
    }


}
