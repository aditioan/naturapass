<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 21/07/14
 * Time: 11:08
 */

namespace NaturaPass\GraphBundle\Repository;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use NaturaPass\UserBundle\Entity\User;

class EdgeRepository extends EntityRepository
{

    /**
     * Retourne un tableau de pertinences dont les clés sont les types d'arêtes
     *
     * @return ArrayCollection
     */
    public function getPertinences()
    {
        $result = $this->getEntityManager()->getRepository('NaturaPassGraphBundle:Pertinence')->findAll();

        $pertinences = new ArrayCollection();

        foreach ($result as $pertinence) {
            $pertinences->set($pertinence->getType(), $pertinence);
        }

        return $pertinences;
    }

    /**
     * Retourne les arêtes liées à un objet
     *
     * @param $type
     * @param $objectID
     * @return array
     */
    public function getEdgesByObjectId($type, $objectID)
    {
        $qb = $this->createQueryBuilder('e');

        $qb->where($qb->expr()->eq('e.objectID', $objectID));

        if ($type % 100 == 0) {
            $qb->andWhere($qb->expr()->between('e.type', $type, $type + 99));
        } else {
            $qb->andWhere($qb->expr()->eq('e.type', $type));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les arêtes d'un utilisateur pour un type défini
     *
     * @param User $user
     * @param $type
     * @param null $objectID
     * @return array
     */
    public function getEdgesByUser(User $user, $type = NULL, $objectID = NULL)
    {
        $qb = $this->createQueryBuilder('e');

        $qb->where($qb->expr()->orX(
            $qb->expr()->eq('e.from', ':from'),
            $qb->expr()->eq('e.to', ':to')
        ));
//        $qb->where($qb->expr()->eq('e.from', ':from'));

        if ($type) {
            $qb->andWhere($qb->expr()->eq('e.type', $type));
        }

        if ($objectID) {
            $qb->andWhere($qb->expr()->eq('e.objectID', $objectID));
        }

        $qb->setParameter('from', $user)
            ->setParameter('to', $user);
//        $qb->setParameter('from', $user);
//            ->setParameter('to', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les arrêtes entre les utilisateurs
     *
     * @param User $user1
     * @param User $user2
     * @param $type
     * @param null $objectID
     * @return array
     */
    public function getEdgesByProtagonists(User $user1, User $user2, $type, $objectID = NULL)
    {
        $qb = $this->createQueryBuilder('e');

        $qb->where($qb->expr()->eq('e.type', $type))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->eq('e.from', ':user1'),
                    $qb->expr()->eq('e.to', ':user2')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('e.from', ':user2'),
                    $qb->expr()->eq('e.to', ':user1')
                )
            ))
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2);

        if ($objectID) {
            $qb->andWhere($qb->expr()->eq('e.objectID', $objectID));
        }

        return $qb->getQuery()->getResult();
    }
}