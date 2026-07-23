<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function add(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getSourceCounts(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('u.registerSource as source, COUNT(t.id) as cnt')
            ->leftJoin('t.user', 'u')
            ->groupBy('u.registerSource')
            ->getQuery()
            ->getScalarResult();

        $result = ['web' => 0, 'mobile' => 0, 'none' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $key = isset($row['source']) && in_array($row['source'], ['web', 'mobile']) ? $row['source'] : 'none';
            $result[$key] += (int) $row['cnt'];
            $result['total'] += (int) $row['cnt'];
        }
        return $result;
    }

    public function findBySourceFilter(string $source): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')
            ->orderBy('t.id', 'DESC');

        if ($source === 'none') {
            $qb->andWhere('t.user IS NULL OR u.registerSource IS NULL');
        } elseif (in_array($source, ['web', 'mobile'])) {
            $qb->andWhere('t.user IS NOT NULL')
               ->andWhere('u.registerSource = :source')
               ->setParameter('source', $source);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne le nombre de transactions et la somme des montants (status = 'approved')
     * groupés par transactionFor pour les 4 services principaux.
     */
    public function getRevenueByService(): array
    {
        $services = ['boost_contact', 'boost_affaire', 're_boost_affaire', 'boost_reseau_sociaux'];

        $rows = $this->createQueryBuilder('t')
            ->select('t.transactionFor as service, COUNT(t.id) as nbr, SUM(t.amount) as total')
            ->where('t.status = :status')
            ->andWhere('t.transactionFor IN (:services)')
            ->setParameter('status', 'approved')
            ->setParameter('services', $services)
            ->groupBy('t.transactionFor')
            ->getQuery()
            ->getScalarResult();

        // Indexer par service pour accès facile dans le template
        $result = [];
        foreach ($services as $svc) {
            $result[$svc] = ['nbr' => 0, 'total' => 0];
        }
        foreach ($rows as $row) {
            $result[$row['service']] = [
                'nbr'   => (int) $row['nbr'],
                'total' => (int) $row['total'],
            ];
        }
        return $result;
    }

    /**
     * Compte les transactions payées (status = 'approved') pour les services
     * boost_contact, boost_affaire, re_boost_affaire et boost_reseau_sociaux.
     */
    public function countPaidServicesTransactions(\App\Entity\User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->andWhere('t.status = :status')
            ->andWhere('t.transactionFor IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('status', 'approved')
            ->setParameter('types', ['boost_contact', 'boost_affaire', 're_boost_affaire', 'boost_reseau_sociaux'])
            ->getQuery()
            ->getSingleScalarResult();
    }


}
