<?php

namespace App\Repository;

use App\Entity\Preuve;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Preuve>
 *
 * @method Preuve|null find($id, $lockMode = null, $lockVersion = null)
 * @method Preuve|null findOneBy(array $criteria, array $orderBy = null)
 * @method Preuve[]    findAll()
 * @method Preuve[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreuveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Preuve::class);
    }


}
