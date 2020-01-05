<?php

namespace App\Repository;

use App\Entity\TermId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TermId|null find($id, $lockMode = null, $lockVersion = null)
 * @method TermId|null findOneBy(array $criteria, array $orderBy = null)
 * @method TermId[]    findAll()
 * @method TermId[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermIdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TermId::class);
    }

    // /**
    //  * @return TermId[] Returns an array of TermId objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TermId
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
