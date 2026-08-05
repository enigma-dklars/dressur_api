<?php

namespace App\Repository;

use App\Entity\EnvMailSender;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EnvMailSender>
 *
 * @method EnvMailSender|null find($id, $lockMode = null, $lockVersion = null)
 * @method EnvMailSender|null findOneBy(array $criteria, array $orderBy = null)
 * @method EnvMailSender[]    findAll()
 * @method EnvMailSender[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnvMailSenderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnvMailSender::class);
    }


}
