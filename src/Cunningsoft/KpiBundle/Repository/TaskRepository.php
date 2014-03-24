<?php

namespace Cunningsoft\KpiBundle\Repository;

use Cunningsoft\KpiBundle\Entity\Task;
use Doctrine\ORM\EntityRepository;

class TaskRepository extends EntityRepository
{
    /**
     * @param int $limit
     *
     * @return Task[]
     */
    public function findPending($limit)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('t');
        $qb->from('KpiBundle:Task', 't');
        $qb->where('t.nextExecutionDate  < :now');
        $qb->setParameter('now', new \DateTime());
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}