<?php

namespace Api\ApiBundle\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Admin\GameBundle\Entity\Game;
use Api\ApiBundle\Controller\v1\ApiRestController;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class GameController extends ApiRestController {

    /**
     * Get games which are opens
     *
     * GET /admin/games/open?offset=0&limit=10
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GameDetail", "GameLess"})
     */
    public function getGamesOpenAction(Request $request) {
        $qb = $this->getDoctrine()->getRepository('AdminGameBundle:Game')->createQueryBuilder('g');

        $games = $qb->setFirstResult($request->request->get('offset', 0))
            ->setMaxResults($request->request->get('limit', 10))
            ->andWhere('g.debut <= :endDate')
            ->andWhere('g.fin >= :endDate')
            ->setParameter('endDate', new \DateTime())
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->getResult();

        $allGames = new ArrayCollection();

        foreach ($games as $game) {
            $mGame = $this->getFormatGame($game);
            $allGames->add($mGame);
        }

        return $this->view(array('games' => $allGames), Codes::HTTP_OK);
    }

    /**
     * Get games which are closed
     *
     * GET /admin/games/closed?offset=0&limit=10
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GameDetail", "GameLess"})
     */
    public function getGamesClosedAction(Request $request) {
        $qb = $this->getDoctrine()->getRepository('AdminGameBundle:Game')->createQueryBuilder('g');

        $games = $qb->setFirstResult($request->request->get('offset', 0))
            ->setMaxResults($request->request->get('limit', 10))
            ->andWhere('g.debut < :endDate')
            ->andWhere('g.fin < :endDate')
            ->setParameter('endDate', new \DateTime())
            ->orderBy('g.created', 'DESC')
            ->getQuery()
            ->getResult();
        $allGames = new ArrayCollection();

        foreach ($games as $game) {
            $mGame = $this->getFormatGame($game);
            $allGames->add($mGame);
        }

        return $this->view(array('games' => $allGames), Codes::HTTP_OK);
    }

    /**
     * Get a game
     *
     * GET /admin/game/{game_id}
     *
     * @param Game $game
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("game", class="AdminGameBundle:Game")
     */
    public function getGameAction(Game $game) {
        return $this->view(array('game' => $this->getFormatLongGame($game)), Codes::HTTP_OK);
    }

    /**
     * Get the games
     *
     * GET /admin/games?offset=0&limit=10
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GameDetail", "GameLess"})
     */
    public function getGamesAction(Request $request) {
        $qb = $this->getDoctrine()->getRepository('AdminGameBundle:Game')->createQueryBuilder('g');

        $games = $qb->setFirstResult($request->request->get('offset', 0))
                ->setMaxResults($request->request->get('limit', 10))
                ->orderBy('g.created', 'DESC')
                ->getQuery()
                ->getResult();

        return $this->view(array('games' => $games), Codes::HTTP_OK);
    }

    /**
     * Remove a game
     *
     * DELETE /admin/game/{game_id}
     *
     * @param Game $game
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("game", class="AdminGameBundle:Game")
     */
    public function deleteGameAction(Game $game) {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($game);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

}
