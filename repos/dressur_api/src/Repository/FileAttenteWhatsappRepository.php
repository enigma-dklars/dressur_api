<?php

namespace App\Repository;

use App\Entity\FileAttenteWhatsapp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FileAttenteWhatsappRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileAttenteWhatsapp::class);
    }

    public function findRecentlyContactedPhones(array $phones, int $cooldownDays, array $titres): array
    {
        if (empty($phones) || empty($titres)) {
            return [];
        }

        $cutoff = new \DateTime("-{$cooldownDays} days");

        $rows = $this->createQueryBuilder('f')
            ->select('f.sendto AS phone')
            ->where('f.sendto IN (:phones)')
            ->andWhere('f.titre IN (:titres)')
            ->andWhere('f.createdAt >= :cutoff')
            ->setParameter('phones', $phones)
            ->setParameter('titres', $titres)
            ->setParameter('cutoff', $cutoff)
            ->distinct()
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'phone');
    }

    public function countByStatut(string $statut): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
