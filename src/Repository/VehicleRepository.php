<?php

namespace App\Repository;

use App\Entity\Vehicle;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * Retourne tous les véhicules appartenant à un utilisateur
     **/
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.owner = :user') 
            ->setParameter('user', $user)
            ->orderBy('v.marque', 'ASC') 
            ->addOrderBy('v.modele', 'ASC') 
            ->getQuery()
            ->getResult();
    }
}
