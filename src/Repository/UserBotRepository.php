<?php

namespace App\Repository;

use App\Entity\UserBot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBot>
 *
 * @method UserBot|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBot|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBot[]    findAll()
 * @method UserBot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBot::class);
    }


}
