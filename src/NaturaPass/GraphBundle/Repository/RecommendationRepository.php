<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 04/08/14
 * Time: 11:23
 */

namespace NaturaPass\GraphBundle\Repository;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use NaturaPass\GraphBundle\Entity\Recommendation;
use NaturaPass\UserBundle\Entity\User;

class RecommendationRepository extends EntityRepository
{

    /**
     * Supprime une recommendation entre deux utilisateurs
     *
     * @param User $u1
     * @param User $u2
     */
    public function deleteRecommendationBetween(User $u1, User $u2)
    {
        $recommendation = $this->findOneBy(array(
            'owner' => $u1,
            'target' => $u2
        ));

        if (is_object($recommendation)) {
            $this->getEntityManager()->remove($recommendation);
        }

        $recommendation2 = $this->findOneBy(array(
            'owner' => $u2,
            'target' => $u1
        ));

        if (is_object($recommendation2)) {
            $this->getEntityManager()->remove($recommendation2);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Supprime une recommendation entre deux utilisateurs
     *
     * @param User $owner
     */
    public function deleteByOwner(User $owner)
    {
        $recommendations = $this->findBy(
            array('owner' => $owner)
        );

        foreach ($recommendations as $recommendation) {
            if ($recommendation->getAction() != Recommendation::ACTION_REMOVED) {
                $this->getEntityManager()->remove($recommendation);
            }
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Retourne toutes les recommendations d'un utilisateur
     *
     * @param User $owner
     * @param int|bool $offset
     * @param int|bool $limit
     *
     * @return ArrayCollection
     */
    public function getByOwner(User $owner, $offset = false, $limit = false, $allAction = false)
    {
        $qb = $this->createQueryBuilder('r');

        $updated = new \DateTime();
        $updated->add(new \DateInterval('P7D'));

        $qb->select('r')
            ->where('r.owner = :owner')
            ->orderBy('r.pertinence', 'DESC')
            ->setParameter('owner', $owner);
        if (!$allAction) {
            $qb->andWhere('r.action <> :action')
                ->setParameter('action', Recommendation::ACTION_REMOVED);
        }
//        if ($offset) {
//            $qb->setFirstResult($offset);
//        }
//
//        if ($limit) {
//            $qb->setMaxResults($limit);
//        }

        $results = $qb->getQuery()->getResult();

        $recommendations = new ArrayCollection();

        foreach ($results as $result) {
            $recommendations->set($result->getTarget()->getId(), $result);
        }
        if (!$offset && !$limit) {
            return $recommendations;
        } else {
            return $recommendations->slice($offset, $limit);
        }
    }
}