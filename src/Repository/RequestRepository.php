<?php

namespace App\Repository;

use App\Entity\Request;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Request>
 */
class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Request::class);
    }

    /**
     * @param int $customerId
     * @param int|null $equipmentId
     * @return Request[]
     */
    public function findByCustomerAndEquipment(int $customerId, ?int $equipmentId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.customer', 'c')
            ->join('r.equipment', 'e')
            ->addSelect('c', 'e')
            ->where('c.id = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('r.created', 'DESC');

        if (null !== $equipmentId) {
            $qb->andWhere('e.id = :equipmentId')
                ->setParameter('equipmentId', $equipmentId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Request[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.customer', 'c')
            ->join('r.equipment', 'e')
            ->addSelect('c', 'e')
            ->where('c.creator = :user')
            ->setParameter('user', $user)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Request[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.customer', 'c')
            ->join('r.equipment', 'e')
            ->addSelect('c', 'e')
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
