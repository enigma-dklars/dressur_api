<?php

namespace App\Repository;

use App\Entity\HistoriqueProgrammeRecompense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueProgrammeRecompense>
 *
 * @method HistoriqueProgrammeRecompense|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriqueProgrammeRecompense|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriqueProgrammeRecompense[]    findAll()
 * @method HistoriqueProgrammeRecompense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriqueProgrammeRecompenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueProgrammeRecompense::class);
    }


}
