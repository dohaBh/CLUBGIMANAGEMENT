<?php

namespace App\Repository;

use App\Entity\DemandeAdhesion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Enum\StatutDmd;

/**
 * @extends ServiceEntityRepository<DemandeAdhesion>
 */
class DemandeAdhesionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeAdhesion::class);
    }


    // Dans DemandeAdhesionRepository.php
public function findByStatut(StatutDmd $statut): array
{
    return $this->createQueryBuilder('d')
        ->where('d.statutDmd = :statut')
        ->setParameter('statut', $statut)
        ->orderBy('d.dateDemande', 'DESC')
        ->getQuery()
        ->getResult();
}

public function countByStatut(): array
{
    return $this->createQueryBuilder('d')
        ->select('d.statutDmd, COUNT(d.id) as count')
        ->groupBy('d.statutDmd')
        ->getQuery()
        ->getResult();
}

public function findRecentDemandes(\DateTimeInterface $date): array
{
    return $this->createQueryBuilder('d')
        ->where('d.dateDemande >= :date')
        ->setParameter('date', $date)
        ->orderBy('d.dateDemande', 'DESC')
        ->getQuery()
        ->getResult();
}

    //    /**
    //     * @return DemandeAdhesion[] Returns an array of DemandeAdhesion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DemandeAdhesion
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
