<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    // Trouve les événements à venir (date >= aujourd'hui)
    public function findUpcomingEvents(\DateTimeInterface $today): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.date >= :today')
            ->setParameter('today', $today)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Trouve les événements passés (date < aujourd'hui)
    public function findPastEvents(\DateTimeInterface $today): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.date < :today')
            ->setParameter('today', $today)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}