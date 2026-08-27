<?php

namespace App\Repository;

use App\Entity\UserBanned;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBanned>
 */
class UserBannedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBanned::class);
    }

    public function normalizeTel(?string $tel): ?string
    {
        $value = trim((string) $tel);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9+]/', '', $value) ?? '';
        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }

        return $value === '' || $value === '+' ? null : $value;
    }

    public function normalizeMail(?string $mail): ?string
    {
        $value = strtolower(trim((string) $mail));
        return $value === '' ? null : $value;
    }

    public function existsByTel(?string $tel): bool
    {
        if ($tel === null || $tel === '') {
            return false;
        }

        return $this->count(['tel' => $tel]) > 0;
    }

    public function existsByMail(?string $mail): bool
    {
        if ($mail === null || $mail === '') {
            return false;
        }

        return $this->count(['mail' => $mail]) > 0;
    }

    public function findOneByIdentity(?string $tel, ?string $mail): ?UserBanned
    {
        $query = $this->createQueryBuilder('banned')
            ->setMaxResults(1);

        $expressions = [];
        if ($tel !== null) {
            $expressions[] = 'banned.tel = :tel';
            $query->setParameter('tel', $tel);
        }
        if ($mail !== null) {
            $expressions[] = 'banned.mail = :mail';
            $query->setParameter('mail', $mail);
        }
        if ($expressions === []) {
            return null;
        }

        return $query
            ->andWhere('(' . implode(' OR ', $expressions) . ')')
            ->orderBy('banned.createdAt', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return UserBanned[]
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('banned')
            ->select('COUNT(banned.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
