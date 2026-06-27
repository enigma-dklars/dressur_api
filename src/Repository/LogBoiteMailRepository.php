<?php

namespace App\Repository;

use App\Entity\LogBoiteMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogBoiteMail>
 *
 * @method LogBoiteMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBoiteMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBoiteMail[]    findAll()
 * @method LogBoiteMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBoiteMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBoiteMail::class);
    }

    public function add(LogBoiteMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LogBoiteMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne les logs filtrés (max 500) triés par date décroissante.
     *
     * @param array{raison?: string, sender?: string, date_from?: string, date_to?: string} $filters
     * @return LogBoiteMail[]
     */
    public function findFiltered(array $filters = [], int $limit = 500): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.datEnvoi', 'DESC')
            ->setMaxResults($limit);

        if (!empty($filters['raison'])) {
            $qb->andWhere('l.raison = :raison')
               ->setParameter('raison', $filters['raison']);
        }

        if (!empty($filters['sender'])) {
            $qb->andWhere('l.emailSender = :sender')
               ->setParameter('sender', $filters['sender']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('l.datEnvoi >= :date_from')
               ->setParameter('date_from', new \DateTime($filters['date_from'] . ' 00:00:00'));
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('l.datEnvoi <= :date_to')
               ->setParameter('date_to', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre d'envois par compte sender (pour stats round-robin).
     *
     * @return array<array{sender: string, total: int}>
     */
    public function getStatsBySender(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.emailSender AS sender, COUNT(l.id) AS total')
            ->groupBy('l.emailSender')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Liste des raisons distinctes (pour le filtre dropdown).
     *
     * @return array<array{raison: string}>
     */
    public function getDistinctRaisons(): array
    {
        return $this->createQueryBuilder('l')
            ->select('DISTINCT l.raison AS raison')
            ->orderBy('l.raison', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Liste des senders distincts (pour le filtre dropdown).
     *
     * @return array<array{sender: string}>
     */
    public function getDistinctSenders(): array
    {
        return $this->createQueryBuilder('l')
            ->select('DISTINCT l.emailSender AS sender')
            ->orderBy('l.emailSender', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
