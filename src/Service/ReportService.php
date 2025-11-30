<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Request;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Сервис для выборок, используемых в разделe отчётов.
 */
final class ReportService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return array<int, array{equipment: string, request: string, customer: string, sum: int}>
     */
    public function getDebitorka(User $user, bool $isAdmin): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('equipment.name AS equipmentName')
            ->addSelect('request.name AS requestName')
            ->addSelect('customer.name AS customerName')
            ->addSelect('COALESCE(SUM(payment.sum), 0) AS balance')
            ->from(Payment::class, 'payment')
            ->join('payment.request', 'request')
            ->join('request.customer', 'customer')
            ->join('request.equipment', 'equipment')
            ->groupBy('equipment.id')
            ->addGroupBy('request.id')
            ->addGroupBy('customer.id')
            ->having('SUM(payment.sum) < 0')
            ->orderBy('customer.name', 'ASC')
            ->addOrderBy('request.name', 'ASC');

        if (!$isAdmin) {
            $qb->andWhere('customer.creator = :user')
                ->setParameter('user', $user);
        }

        $rows = $qb->getQuery()->getResult();

        return array_map(static function (array $row): array {
            $balance = (int)($row['balance'] ?? 0);

            return [
                'equipment' => (string)$row['equipmentName'],
                'request' => (string)$row['requestName'],
                'customer' => (string)$row['customerName'],
                'sum' => intdiv($balance, 100),
            ];
        }, $rows);
    }

    /**
     * @return array<int, array{customer: string, equipment: string, request: string, sum: int}>
     */
    public function getTotalPayed(User $user, bool $isAdmin): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('customer.name AS customerName')
            ->addSelect('equipment.name AS equipmentName')
            ->addSelect('request.name AS requestName')
            ->addSelect('COALESCE(SUM(CASE WHEN payment.sum > 0 THEN payment.sum ELSE 0 END), 0) AS positiveTotal')
            ->from(Payment::class, 'payment')
            ->join('payment.request', 'request')
            ->join('request.customer', 'customer')
            ->join('request.equipment', 'equipment')
            ->where('request.status IN (:statuses)')
            ->setParameter('statuses', [Request::STATUS_NEW, Request::STATUS_IN_PROGRESS])
            ->groupBy('customer.id')
            ->addGroupBy('equipment.id')
            ->addGroupBy('request.id')
            ->having('SUM(CASE WHEN payment.sum > 0 THEN payment.sum ELSE 0 END) > 0')
            ->orderBy('customer.name', 'ASC')
            ->addOrderBy('request.name', 'ASC');

        if (!$isAdmin) {
            $qb->andWhere('customer.creator = :user')
                ->setParameter('user', $user);
        }

        $rows = $qb->getQuery()->getResult();

        return array_map(static function (array $row): array {
            $total = (int)($row['positiveTotal'] ?? 0);

            return [
                'customer' => (string)$row['customerName'],
                'equipment' => (string)$row['equipmentName'],
                'request' => (string)$row['requestName'],
                'sum' => intdiv($total, 100),
            ];
        }, $rows);
    }

    /**
     * @return array<int, array{month: string, sum: int}>
     */
    public function getSalaryByMonth(User $user, bool $isAdmin): array
    {
        $connection = $this->em->getConnection();

        $sql = <<<SQL
            SELECT
                YEAR(p.created) AS year_num,
                MONTH(p.created) AS month_num,
                /*SUM(CASE WHEN p.sum > 0 THEN p.sum ELSE 0 END) AS total*/
                SUM(p.sum) AS total 
            FROM payment p
                INNER JOIN request r ON r.id = p.request_id
                INNER JOIN customer c ON c.id = r.customer_id
            %s
            GROUP BY year_num, month_num
            ORDER BY year_num DESC, month_num DESC
        SQL;

        $conditions = ['r.status IN (:statuses)'];
        $params = [
            'statuses' => [Request::STATUS_NEW, Request::STATUS_IN_PROGRESS],
        ];
        $types = [
            'statuses' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
        ];

        if (!$isAdmin) {
            $conditions[] = 'c.creator_id = :creatorId';
            $params['creatorId'] = $user->getId();
            $types['creatorId'] = \PDO::PARAM_INT;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        $finalSql = sprintf($sql, $whereSql);
        $rows = $connection->executeQuery($finalSql, $params, $types)->fetchAllAssociative();

        $monthNames = [
            1 => 'январь',
            2 => 'февраль',
            3 => 'март',
            4 => 'апрель',
            5 => 'май',
            6 => 'июнь',
            7 => 'июль',
            8 => 'август',
            9 => 'сентябрь',
            10 => 'октябрь',
            11 => 'ноябрь',
            12 => 'декабрь',
        ];

        return array_map(static function (array $row) use ($monthNames): array {
            $year = (int) ($row['year_num'] ?? 0);
            $month = (int) ($row['month_num'] ?? 0);
            $total = (int) ($row['total'] ?? 0);

            $label = ($year > 0 && $month > 0)
                ? sprintf('%s %04d', $monthNames[$month] ?? sprintf('%02d', $month), $year)
                : '';

            return [
                'month' => $label,
                'sum' => intdiv($total, 100),
            ];
        }, $rows);
    }
}
