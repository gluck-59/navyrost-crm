<?php

namespace App\Repository;

use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Customer;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<Equipment>
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    /**
     * @return Equipment[]
     */
    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Equipment[]
     */
    public function findAvailableForUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.customer', 'c')
            ->where('c.creator = :user')
            ->setParameter('user', $user)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
