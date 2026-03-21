<?php

namespace App\Repository;

use App\Entity\Formation;
use App\Enum\StatutFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatut(StatutFormation $statut): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.statutFormation = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('f.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFormationsAVenir(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.date >= :today')
            ->setParameter('today', new \DateTime())
            ->andWhere('f.statutFormation = :statut')
            ->setParameter('statut', StatutFormation::PLANIFIEE)
            ->orderBy('f.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}