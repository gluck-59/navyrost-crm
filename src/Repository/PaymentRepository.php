<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\Request;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * @return Payment[]
     */
    public function findByRequest(Request $request): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.request = :request')
            ->setParameter('request', $request)
            ->orderBy('p.created', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{income:int, expense:int}
     */
    public function getTotalsByRequest(Request $request): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(CASE WHEN p.sum > 0 THEN p.sum ELSE 0 END), 0) AS income')
            ->addSelect('COALESCE(SUM(CASE WHEN p.sum < 0 THEN p.sum ELSE 0 END), 0) AS expense')
            ->where('p.request = :request')
            ->setParameter('request', $request)
            ->getQuery()
            ->getSingleResult();

        return [
            'income' => (int) $result['income'],
            'expense' => (int) $result['expense'],
        ];
    }

    /**
     * @param int[] $requestIds
     * @return array<int, int>
     */
    public function getSumByRequestIds(array $requestIds): array
    {
        if ($requestIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.request) AS request_id')
            ->addSelect('COALESCE(SUM(p.sum), 0) AS balance')
            ->where('p.request IN (:requestIds)')
            ->setParameter('requestIds', $requestIds)
            ->groupBy('p.request')
            ->getQuery()
            ->getResult();

        $balances = [];
        foreach ($rows as $row) {
            $balances[(int) $row['request_id']] = (int) $row['balance'];
        }

        return $balances;
    }
}
