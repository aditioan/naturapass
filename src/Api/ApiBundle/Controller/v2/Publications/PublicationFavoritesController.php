<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 21/07/15
 * Time: 09:58
 */

namespace Api\ApiBundle\Controller\v2\Publications;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\FavoriteSerialization;
use Api\ApiBundle\Controller\v2\Serialization\SqliteSerialization;
use Api\ApiBundle\Controller\v2\Serialization\UserSerialization;
use FOS\RestBundle\Util\Codes;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\PublicationBundle\Form\Handler\FavoriteHandler;
use NaturaPass\PublicationBundle\Form\Type\FavoriteType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class PublicationFavoritesController extends ApiRestController
{

    /**
     * get specific favorite
     *
     * GET /v2/publications/{favorite_id}/favorite
     *
     * @param \NaturaPass\PublicationBundle\Entity\Favorite $favorite
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("favorite", class="NaturaPassPublicationBundle:Favorite")
     */
    public function getFavoriteAction(Favorite $favorite)
    {
        $this->authorize();
        $this->authorizeFavorite($favorite);
        return $this->view(
            array('favorite' => FavoriteSerialization::serializeFavorite($favorite, $this->getUser())), Codes::HTTP_OK
        );
    }

    /**
     * get all favorites of current user
     *
     * GET /v2/publication/favorites?limit=20&offset=20
     *
     * limit:   numbers of elements
     * offset:  begining position
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getFavoritesAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $favorites = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('f')
            ->from('NaturaPassPublicationBundle:Favorite', 'f')
            ->where('f.owner = :owner')
            ->setParameter('owner', $this->getUser())
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('f.created', 'DESC')
            ->getQuery()
            ->getResult();
        return $this->view(
            array('favorites' => FavoriteSerialization::serializeFavorites($favorites, $this->getUser())), Codes::HTTP_OK
        );
    }

    /**
     * Add a favorite
     *
     * POST /v2/publications/favorites
     *
     * {
     *     "favorite": {
     *       "name": "premier fav",
     *       "legend": "ma legend",
     *       "sharing":{"share":1,"withouts":[]},
     *       "groups": [206],
     *       "hunts": [],
     *       "publicationcolor": "4",
     *       "category": 137,
     *       "card": 14,
     *       "specific": 0,
     *       "animal": null,
     *       "attachments":[{"label":32,"value":1},{"label":34,"value":1}]
     *     }
     * }
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postFavoriteAction(Request $request)
    {
        $this->authorize();
        $favorite = new Favorite();
        $params = $request->request->get('favorite');
        if (empty($params["specific"])) {
            $params["specific"] = 0;
        }
        $arrayFavorite = array(
            'favorite' => $params
        );
        $requestFavorite = new Request($_GET, $arrayFavorite, array(), $_COOKIE, $_FILES, $_SERVER);
        $form = $this->createForm(new FavoriteType($this->getSecurityTokenStorage(), $this->container), $favorite, array('csrf_protection' => false));
        $handler = new FavoriteHandler(
            $form, $requestFavorite, $this->getDoctrine()->getManager(), $this->getSecurityTokenStorage()
        );

        if ($favorite = $handler->process()) {
            $manager = $this->getDoctrine()->getManager();

            $manager->persist($favorite);
            $manager->flush();

            $datas = UserSerialization::serializeFavoriteSqliteRefresh(array(), false, $favorite, $this->getUser(),false);
            $sqlite = SqliteSerialization::serializeSqliteInserOrReplace("tb_favorite", array($datas));

            return $this->view(array('favorite' => FavoriteSerialization::serializeFavorite($favorite, $this->getUser()), "sqlite" => $sqlite), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Edit a favorite
     *
     * PUT /v2/publications/{favorite_id}/favorite
     *
     * {
     *     "favorite": {
     *       "name": "premier fav",
     *       "legend": "ma legend",
     *       "sharing":{"share":1,"withouts":[]},
     *       "groups": [206],
     *       "hunts": [],
     *       "publicationcolor": "4",
     *       "category": 137,
     *       "card": 14,
     *       "specific": 0,
     *       "animal": null,
     *       "attachments":[{"label":32,"value":1},{"label":34,"value":1}]
     *     }
     * }
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("favorite", class="NaturaPassPublicationBundle:Favorite")
     *
     */
    public function putFavoriteAction(Request $request, Favorite $favorite)
    {
        $this->authorize();
        $this->authorizeFavorite($favorite);
        $form = $this->createForm(new FavoriteType($this->getSecurityTokenStorage(), $this->container), $favorite, array('csrf_protection' => false, 'method' => 'PUT'));
        $handler = new FavoriteHandler(
            $form, $request, $this->getDoctrine()->getManager(), $this->getSecurityTokenStorage()
        );

        if ($favorite = $handler->process()) {
            $manager = $this->getDoctrine()->getManager();

            $manager->persist($favorite);
            $manager->flush();

            $manager->flush();

            return $this->view(array('favorite' => FavoriteSerialization::serializeFavorite($favorite, $this->getUser())), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Remove a favorite
     *
     * DELETE /v2/publications/{favorite_id}/favorite
     *
     * @param Favorite $favorite
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("favorite", class="NaturaPassPublicationBundle:Favorite")
     */
    public function deleteFavoriteAction(Favorite $favorite)
    {
        $this->authorize();
        $this->authorizeFavorite($favorite);

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($favorite);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }
}
