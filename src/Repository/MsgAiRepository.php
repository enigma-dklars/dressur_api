<?php

namespace App\Repository;

use App\Entity\MsgAi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MsgAi>
 *
 * @method MsgAi|null find($id, $lockMode = null, $lockVersion = null)
 * @method MsgAi|null findOneBy(array $criteria, array $orderBy = null)
 * @method MsgAi[]    findAll()
 * @method MsgAi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MsgAiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MsgAi::class);
    }


}
