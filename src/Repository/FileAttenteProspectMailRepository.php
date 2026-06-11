<?php

namespace App\Repository;

use App\Entity\FileAttenteProspectMail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileAttenteProspectMail>
 *
 * @method FileAttenteProspectMail|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileAttenteProspectMail|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileAttenteProspectMail[]    findAll()
 * @method FileAttenteProspectMail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileAttenteProspectMailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileAttenteProspectMail::class);
    }

    public function add(FileAttenteProspectMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FileAttenteProspectMail $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Returns the set of email addresses (lowercased) that already received a
     * reactivation email within the last $cooldownDays days.
     *
     * @param  string[] $emails          Candidate addresses to check.
     * @param  int      $cooldownDays    How many days to look back.
     * @param  string[] $reactivTitres   Exact `titre` values used for reactivation mails.
     * @return string[]                  Lowercased addresses already contacted recently.
     */
    public function findRecentlyContactedEmails(
        array $emails,
        int $cooldownDays,
        array $reactivTitres
    ): array {
        if (empty($emails) || empty($reactivTitres)) {
            return [];
        }

        $cutoff = new \DateTime("-{$cooldownDays} days");

        $rows = $this->createQueryBuilder('f')
            ->select('LOWER(f.sendto) AS email')
            ->where('LOWER(f.sendto) IN (:emails)')
            ->andWhere('f.titre IN (:titres)')
            ->andWhere('f.createdAt >= :cutoff')
            ->setParameter('emails', array_map('strtolower', $emails))
            ->setParameter('titres', $reactivTitres)
            ->setParameter('cutoff', $cutoff)
            ->distinct()
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'email');
    }
}
