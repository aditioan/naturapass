<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 13/05/14
 * Time: 16:54
 */

namespace NaturaPass\GraphBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\GraphBundle\Entity\Graph;
use NaturaPass\GraphBundle\Entity\Pertinence;
use NaturaPass\GraphBundle\Entity\Recommendation;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;

class GraphService
{

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $manager;

    /**
     * @var \NaturaPass\GraphBundle\Entity\Graph
     */
    protected $graph;

    /**
     * Construit le service
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        $this->graph = $this->manager->getRepository('NaturaPassGraphBundle:Graph')->findAll();

        /**
         * Si aucun Graphe n'existe
         */
        if (!$this->graph) {
            $this->graph = new Graph();

            $this->manager->persist($this->graph);
            $this->manager->flush();
        } else {
            $this->graph = $this->graph[0];
        }
    }

    /**
     * Gère les recommendations d'utilisateurs
     *
     * @param User $user
     *
     * @return ArrayCollection|\NaturaPass\GraphBundle\Entity\Recommendation[]
     */
    public function generateUserRecommendations(User $user)
    {
        $pertinences = $this->manager->getRepository('NaturaPassGraphBundle:Edge')->getPertinences(); // Récupère les pertinences (pertes et pertinances)
        $edges = $this->manager->getRepository('NaturaPassGraphBundle:Edge')->getEdgesByUser($user);  // Récupère les arretes de l'utilisateur

        $this->manager->getRepository('NaturaPassGraphBundle:Recommendation')->deleteByOwner($user); // Supprime les recommendations de l'utilisateur
        $recommendations = $this->manager->getRepository('NaturaPassGraphBundle:Recommendation')->getByOwner($user, false, false, true);  // Récupère les recommendations de l'utilisateur (mise à jours)
        foreach ($edges as $edge) {
            $target = $edge->getFrom() == $user ? $edge->getTo() : $edge->getFrom();

            if (!$user->hasFriendshipWith($target, array(UserFriend::ASKED, UserFriend::CONFIRMED))) {
                $relevant = $this->calculatePertinence($edge, $pertinences->get($edge->getType()));

                if ($recommendations->containsKey($target->getId())) {
                    $recommendation = $recommendations->get($target->getId());

                    $recommendation->setPertinence($recommendation->getPertinence() + $relevant);
                } else {
                    $recommendation = new Recommendation();
                    $recommendation->setOwner($user)
                        ->setTarget($target)
                        ->setPertinence($relevant + count($target->getMutualFriendsWith($user)) * $pertinences->get(Edge::FRIENDSHIP_MUTUAL_FRIEND)->getValue());
                }

                $recommendations->set($target->getId(), $recommendation);
            }
        }

        $older = $this->manager->getRepository('NaturaPassGraphBundle:Recommendation')->getByOwner($user);

        foreach ($recommendations as $key => $recommendation) {
            if ($older->containsKey($key)) {
                $old = $older->get($key);

                $pertinence = $recommendation->getPertinence();

                /**
                 * On calcule l'intervalle entre les aujourd'hui et la dernière mise à jour de la recommendation
                 *
                 * On applique de base, un coefficient de 1 auquel on ajoute le nombre de jours d'écart multiplié par une perte arbitraire
                 *
                 * Si ce coefficient est supérieur à 1, on applique la perte de pertinence à la recommendation, égale au type d'action porté à la puissance du coefficient
                 * Si il est égale, on mets une pertinence égale
                 *
                 * Ce qui fait que pendant 100 jours, la recommendation va décroitre en pertinence puis revenir à sa valeur de départ
                 */
                $interval = $old->getCreated()->diff(new \DateTime())->d;

                if ($interval > 100) {
                    $old->setCreated(new \DateTime());
                }

                $coeff = 1 + $interval * Recommendation::DEFAULT_LOSS;
                $pertinence = $coeff > 1 ? $pertinence - $old->getAction() ^ $coeff : $pertinence;

                $old->setPertinence($pertinence > 0 ? $pertinence : 0.00);

                $this->manager->persist($old);
                $recommendations->set($key, $old);
            } else {
                $this->manager->persist($recommendation);
            }
        }

        $this->manager->flush();

        $iterator = $recommendations->getIterator();

        $iterator->uasort(function ($first, $second) {
            return $first->getPertinence() >= $second->getPertinence();
        });

        $sorted = new ArrayCollection(iterator_to_array($iterator));

        return $sorted;
    }

    /**
     * Calcule une pertinence selon une arête et une pertinence lié
     *
     * @param $edge
     * @param Pertinence $pertinence
     * @return float
     */
    protected function calculatePertinence($edge, $pertinence)
    {
        $interval = $edge->getUpdated()->diff(new \DateTime());
        if ($pertinence instanceof Pertinence) {
            $relevance = $pertinence->getValue() - $pertinence->getLoss() * $interval->d;
        }

        return $relevance > 0.00 ? $relevance : 0.00;
    }

    /**
     * Ajout une relation entre deux utilisateurs si elle n'existe pas déjà
     *
     * @param User $from
     * @param mixed $to
     * @param integer $type
     * @param string|NULL $objectID
     */
    public function generateEdge(User $from, $to, $type, $objectID = NULL)
    {
        if ($to instanceof ArrayCollection) {
            foreach ($to as $receiver) {
                $this->persistEdge($from, $receiver, $type, $objectID);
            }
        } else if (is_array($to)) {
            foreach ($to as $receiver) {
                $this->persistEdge($from, $receiver, $type, $objectID);
            }
        } else if ($to instanceof User) {
            $this->persistEdge($from, $to, $type, $objectID);
        }
    }

    /**
     * Ajoute une arête en base de données
     *
     * @param User $from
     * @param User $to
     * @param integer $type
     * @param string $objectID
     */
    protected function persistEdge(User $from, User $to, $type, $objectID = NULL)
    {
        if ($to != $from) {
            $edge = new Edge();

            $edge->setFrom($from)
                ->setTo($to)
                ->setType($type)
                ->setObjectID($objectID);

            if (!$this->graph->isEdgeExisting($edge)) {
                $edge->setGraph($this->graph);
                $this->graph->addEdge($edge);

                $this->manager->persist($edge);
                $this->manager->flush();
            }
        }
    }

    /**
     * Supprime une arrête ou plusieurs arrêtes
     *
     * @param mixed $edges
     */
    protected function removeEdges($edges)
    {
        if ($edges instanceof ArrayCollection) {
            foreach ($edges as $edge) {
                $this->manager->remove($edge);
            }
        } else if (is_array($edges)) {
            foreach ($edges as $edge) {
                $this->manager->remove($edge);
            }
        } else if ($edges instanceof Edge) {
            $this->manager->remove($edges);
        }

        $this->manager->flush();

        $this->manager->refresh($this->graph);
    }

    /**
     * Supprime une arête entre deux utilisateurs
     *
     * @param User $user1
     * @param User $user2
     * @param integer $type
     * @param mixed $objectID
     */
    public function deleteEdgeBetween(User $user1, User $user2, $type, $objectID = NULL)
    {
        $edges = $this->manager->getRepository('NaturaPassGraphBundle:Edge')->getEdgesByProtagonists($user1, $user2, $type, $objectID);

        $this->removeEdges($edges);
    }

    /**
     * Supprime les arêtes d'un utilisateur donné
     *
     * @param User $user
     * @param integer $type
     * @param mixed $objectID
     */
    public function deleteEdgeOf(User $user, $type, $objectID = NULL)
    {
        $edges = $this->manager->getRepository('NaturaPassGraphBundle:Edge')->getEdgesByUser($user, $type, $objectID);
        $this->removeEdges($edges);
    }

    /**
     * Supprime une arête portant sur les critères passés en paramètre
     *
     * @param integer $type
     * @param string $objectID
     */
    public function deleteEdgeByObject($type, $objectID)
    {
        $edges = $this->manager->getRepository('NaturaPassGraphBundle:Edge')->getEdgesByObjectId($type, $objectID);

        $this->removeEdges($edges);
    }

    /**
     * Supprime une arête portant sur les critères passés en paramètre
     *
     * @param array $criteria
     */
    public function deleteEdgeBy(array $criteria)
    {
        $edges = $this->manager->getRepository('NaturaPassGraphBundle:Edge')->findBy($criteria);

        $this->removeEdges($edges);
    }

}
