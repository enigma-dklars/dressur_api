<?php

namespace App\Repository;

use App\Entity\FormuleDressurBot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FormuleDressurBot>
 *
 * @method FormuleDressurBot|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormuleDressurBot|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormuleDressurBot[]    findAll()
 * @method FormuleDressurBot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormuleDressurBotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FormuleDressurBot::class);
    }


}
