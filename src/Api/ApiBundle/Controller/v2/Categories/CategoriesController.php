<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 21/07/15
 * Time: 09:58
 */

namespace Api\ApiBundle\Controller\v2\Categories;

use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\CardCategoryZone;
use Admin\SentinelleBundle\Entity\Category;
use Admin\SentinelleBundle\Entity\Locality;
use Admin\SentinelleBundle\Entity\Zone;
use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\CardSerialization;
use Api\ApiBundle\Controller\v2\Serialization\CategorySerialization;
use Api\ApiBundle\Controller\v2\Serialization\FavoriteSerialization;
use Api\ApiBundle\Controller\v2\Serialization\ReceiverSerialization;
use Api\ApiBundle\Controller\v2\Serialization\AnimalSerialization;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;
use FOS\RestBundle\Util\Codes;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\PublicationBundle\Entity\Favorite;
use NaturaPass\PublicationBundle\Entity\Publication;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CategoriesController extends ApiRestController
{

    /**
     * Retourne les éléments à mettre dans le code natif
     *
     * GET /v2/categories/tocachesystem
     *
     * CARD TYPE DESCRIPTION
     * type = 0 => field input
     * type = 1 => text input
     * type = 10 => integer input
     * type = 11 => float/decimal input
     * type = 20 => date input (11/02/2016)
     * type = 21 => hour input (09:00)
     * type = 22 => date + hour input (11/02/2016 09:00)
     * type = 30 => simple select input (1 choice)
     * type = 31 => select multiple input (several choices)
     * type = 32 => select2 input (several choice, with search (look like search animal))
     * type = 40 => checkbox input
     * type = 50 => radio input
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesTocachesystemAction()
    {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = array();
        $arrayTree["default"] = CategorySerialization::serializeCategoryCaches($trees, false, false);
        $receivers = $em->createQueryBuilder()
            ->select('r')
            ->from('AdminSentinelleBundle:Receiver', 'r')
            ->getQuery()
            ->getResult();
        $receiverGroups = ReceiverSerialization::serializeReceiverGroups($receivers);
        foreach ($receivers as $receiver) {
            $arrayTree["receiver_" . $receiver->getId()] = CategorySerialization::serializeCategoryCaches($trees, false, false, $receiver);
        }

        $arrayCard = array();
        $cards = $em->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 0')
            ->getQuery()
            ->getResult();

        /**
         * @var Card[] $cards
         */
        foreach ($cards as $card) {
            $arrayCard[] = CardSerialization::serializeCard($card);
        }

        $specificCard = $em->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 1')
            ->getQuery()
            ->getResult();

        $animals = $em->createQueryBuilder()->select('a')
            ->from('AdminAnimalBundle:Animal', 'a')
            ->orderBy('a.name_fr', 'ASC')->getQuery()
            ->getResult();

        return $this->view(array('tree' => $arrayTree, 'animals' => AnimalSerialization::serializeAnimals($animals), 'receivers' => $receiverGroups, 'cards' => $arrayCard, 'specific_card' => CardSerialization::serializeCards($specificCard)), Codes::HTTP_OK);
    }

    /**
     * Retourne les éléments à mettre dans le code natif
     *
     * GET /v2/categories/tocachesystem/withoutanimals?receiver=9&receivername=receiver_9
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesTocachesystemWithoutanimalsAction(Request $request)
    {
        $this->authorize();
        $model = $request->query->get('receiver', null);
        $model_name = $request->query->get('receivername', null);

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = array();
        if (is_null($model) && is_null($model_name)) {
            $arrayTree["default"] = CategorySerialization::serializeCategoryCaches($trees, false, false);
        }

        if (is_null($model) && is_null($model_name)) {
            $receivers = $em->createQueryBuilder()
                ->select('r')
                ->from('AdminSentinelleBundle:Receiver', 'r')
                ->getQuery()
                ->getResult();
        } elseif (is_null($model_name)) {
            $receivers = $em->createQueryBuilder()
                ->select('r')
                ->from('AdminSentinelleBundle:Receiver', 'r')
                ->where('r.id = :id_receiver')
                ->setParameter('id_receiver', $model)
                ->getQuery()
                ->getResult();
        } elseif (is_null($model)) {
            $receiver = str_replace("receiver_", "", $model_name);
            $receivers = $em->createQueryBuilder()
                ->select('r')
                ->from('AdminSentinelleBundle:Receiver', 'r')
                ->where('r.id = :id_receiver')
                ->setParameter('id_receiver', $receiver)
                ->getQuery()
                ->getResult();
        }

        $receiverGroups = ReceiverSerialization::serializeReceiverGroups($receivers);
        foreach ($receivers as $receiver) {
            if (is_null($model)) {
                $arrayTree["receiver_" . $receiver->getId()] = CategorySerialization::serializeCategoryCaches($trees, false, false, $receiver);
            } else if (intval($model) == $receiver->getId()) {
                $arrayTree["receiver_" . $receiver->getId()] = CategorySerialization::serializeCategoryCaches($trees, false, false, $receiver);
            }
        }

        $arrayCard = array();
        $cards = $em->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 0')
            ->getQuery()
            ->getResult();

        /**
         * @var Card[] $cards
         */
        foreach ($cards as $card) {
            $arrayCard[] = CardSerialization::serializeCard($card);
        }

        $specificCard = $em->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 1')
            ->getQuery()
            ->getResult();

        $return = array('tree' => $arrayTree, 'receivers' => $receiverGroups, 'cards' => $arrayCard, 'specific_card' => CardSerialization::serializeCards($specificCard));

        return $this->view($return, Codes::HTTP_OK);
    }

    /**
     * Retourn the model to show if publication not geolocated (using cache system)
     *
     * GET /v2/categories/toshow
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesToshowAction()
    {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $receivers = $em->createQueryBuilder()
            ->select('r')
            ->from('AdminSentinelleBundle:Receiver', 'r')
            ->getQuery()
            ->getResult();

        $treeToDisplay = CategorySerialization::serializeCategoryToShow($receivers, $this->getUser());

        return $this->view(array('model' => $treeToDisplay), Codes::HTTP_OK);
    }

    /**
     * Retourne the model or the tree to use if publication geolocated (if param model => use cache system)
     *
     * GET /v2/categories/geolocated?lat=45.778759&lng=5.213104
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesGeolocatedAction(Request $request)
    {
        $this->authorize();
        $latitude = $request->query->get('lat', 0);
        $longitude = $request->query->get('lng', 0);
        if ($latitude != 0 || $longitude != 0) {
            $array_ok = array();
            $array_receiver = array();
            $geolocation = new Geolocation();
            $geolocation->setLatitude($latitude);
            $geolocation->setLongitude($longitude);
            $locality = $this->getGeolocationService()->findACity($geolocation);
            if ($locality instanceof Locality) {
                $em = $this->getDoctrine()->getManager();
                $zone = (is_object($locality) && is_object($locality->getZone())) ? $locality->getZone() : null;
                if (!is_null($zone)) {
                    foreach ($locality->getReceivers() as $receiver) {
                        $array_receiver[$receiver->getId()] = ReceiverSerialization::serializeReceiver($receiver, false);
                        foreach ($receiver->getReceiverrights() as $receiverRight) {
                            $category_id = $receiverRight->getCategory()->getId();
                            if (!array_key_exists($category_id, $array_ok)) {
                                $array_ok[$category_id] = array();
                            }
                            if (!in_array($receiver->getId(), $array_ok[$category_id])) {
                                $array_ok[$category_id][] = $receiver->getId();
                            }
                        }
                    }
                    $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');
                    $arrayTree = CategorySerialization::serializeCategoryPublications($trees, $zone, $array_ok, $array_receiver, false, false, $this->getUser());
                    $arrayFavorite = array();
                    $arrayFavoriteId = array();
                    foreach ($this->getUser()->getFavoritesOwner() as $favorite) {
                        if (is_null($favorite->getCategory()) || is_null($favorite->getCard())) {
                            $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                            $arrayFavoriteId[] = $favorite->getId();
                        } else if ($favorite->getCategory()->getVisible() == Category::VISIBLE_ON) {
                            foreach ($zone->getCards() as $cardCategoryByZone) {
                                if ((!is_null($favorite->getCard()) && $cardCategoryByZone->getCategory()->getId() == $favorite->getCategory()->getId() && $cardCategoryByZone->getCard()->getId() == $favorite->getCard()->getId() && !in_array($favorite->getId(), $arrayFavoriteId)) || is_null($favorite->getCard()) || is_null($favorite->getCategory())) {
                                    $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                                    $arrayFavoriteId[] = $favorite->getId();
                                }
                            }
                        }
                    }
                    return $this->view(array('tree' => $arrayTree, 'favorites' => $arrayFavorite), Codes::HTTP_OK);
                } else {
                    $receivers = $em->createQueryBuilder()
                        ->select('r')
                        ->from('AdminSentinelleBundle:Receiver', 'r')
                        ->getQuery()
                        ->getResult();
                    $treeToDisplay = CategorySerialization::serializeCategoryToShow($receivers, $this->getUser());
                    $arrayFavorite = array();
                    foreach ($this->getUser()->getFavoritesOwner() as $favorite) {
                        if (is_null($favorite->getCategory()) || is_null($favorite->getCard())) {
                            $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                        } else if ((!is_null($favorite->getCard()) && $favorite->getCategory()->getVisible() == Category::VISIBLE_ON && $favorite->getCard()->getId() == $favorite->getCategory()->getCard()->getId()) || is_null($favorite->getCard()) || is_null($favorite->getCategory())) {
                            $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                        }
                    }
//                    return $this->view(array('model' => 'default'), Codes::HTTP_OK);
                    return $this->view(array('model' => $treeToDisplay, 'favorites' => $arrayFavorite), Codes::HTTP_OK);
                }
            } else {
                $arrayFavorite = array();
                foreach ($this->getUser()->getFavoritesOwner() as $favorite) {
                    if (is_null($favorite->getCategory()) || is_null($favorite->getCard())) {
                        $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                    } else if ((!is_null($favorite->getCard()) && $favorite->getCategory()->getVisible() == Category::VISIBLE_ON && $favorite->getCard()->getId() == $favorite->getCategory()->getCard()->getId()) || is_null($favorite->getCard()) || is_null($favorite->getCategory())) {
                        $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                    }
                }
                return $this->view(array('model' => 'default', 'favorites' => $arrayFavorite), Codes::HTTP_OK);
            }
        }else{
            $arrayFavorite = array();
            foreach ($this->getUser()->getFavoritesOwner() as $favorite) {
                if (is_null($favorite->getCategory()) || is_null($favorite->getCard())) {
                    $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                } else if ((!is_null($favorite->getCard()) && $favorite->getCategory()->getVisible() == Category::VISIBLE_ON && $favorite->getCard()->getId() == $favorite->getCategory()->getCard()->getId()) || is_null($favorite->getCard()) || is_null($favorite->getCategory())) {
                    $arrayFavorite[] = FavoriteSerialization::serializeFavoriteLess($favorite);
                }
            }
            return $this->view(array('model' => 'default', 'favorites' => $arrayFavorite), Codes::HTTP_OK);
        }
//        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Retourne l'arborescence générale
     *
     * GET /v2/categories
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesAction()
    {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = CategorySerialization::serializeCategorys($trees, false, false, $this->getUser());
        $arrayCard = array();

        $cards = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 0')
            ->getQuery()
            ->getResult();

        /**
         * @var Card[] $cards
         */
        foreach ($cards as $card) {
            $arrayCard[$card->getId()] = CardSerialization::serializeCard($card);
        }

        $specificCard = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 1')
            ->getQuery()
            ->getResult();

        return $this->view(array('tree' => $arrayTree, 'cards' => $arrayCard, 'specific_card' => CardSerialization::serializeCards($specificCard)), Codes::HTTP_OK);
    }

    /**
     * Retourne l'arborescence générale
     *
     * GET /v2/categories/mobile
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesMobileAction()
    {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = CategorySerialization::serializeCategorys($trees, false, false, $this->getUser());
        $arrayCard = array();

        $cards = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 0')
            ->getQuery()
            ->getResult();

        /**
         * @var Card[] $cards
         */
        foreach ($cards as $card) {
            $arrayCard[] = CardSerialization::serializeCard($card);
        }

        $specificCard = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 1')
            ->getQuery()
            ->getResult();

        return $this->view(array('tree' => $arrayTree, 'cards' => $arrayCard, 'specific_card' => CardSerialization::serializeCards($specificCard)), Codes::HTTP_OK);
    }

    /**
     * Retourne l'arborescence générale
     *
     * GET /v2/categories/map
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesMapAction()
    {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = CategorySerialization::serializeCategorys($trees, false, false, $this->getUser());

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);
    }

    /**
     * Retourne l'arborescence de la publication
     *
     * GET /v2/categories/{ID_PUBLICATION}/publication
     *
     * @param Publication $publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getCategoriesPublicationAction(Publication $publication)
    {
        $this->authorize();
        $this->authorizePublication($publication);

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $array_ok = array();
        $array_receiver = array();
        $locality = $publication->getLocality();
        $zone = (is_object($locality) && is_object($locality->getZone())) ? $locality->getZone() : null;
        if (!is_null($zone)) {
            if ($locality instanceof Locality) {
                foreach ($locality->getReceivers() as $receiver) {
                    $array_receiver[$receiver->getId()] = ReceiverSerialization::serializeReceiver($receiver, false);
                    foreach ($receiver->getReceiverrights() as $receiverRight) {
                        $category_id = $receiverRight->getCategory()->getId();
                        if (!array_key_exists($category_id, $array_ok)) {
                            $array_ok[$category_id] = array();
                        }
                        if (!in_array($receiver->getId(), $array_ok[$category_id])) {
                            $array_ok[$category_id][] = $receiver->getId();
                        }
                    }
                }
            }
            $arrayTree = CategorySerialization::serializeCategoryPublications($trees, $zone, $array_ok, $array_receiver, false, false, $this->getUser());
        } else {
            $arrayTree = CategorySerialization::serializeCategorys($trees, false, false, $this->getUser());
        }
        $arrayCard = array();

        /**
         * @var Card[] $cards
         */
        $cards = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 0')
            ->getQuery()
            ->getResult();

        foreach ($cards as $card) {
            $arrayCard[$card->getId()] = CardSerialization::serializeCard($card);
        }


        $specificCard = $this->getDoctrine()->getManager()
            ->createQueryBuilder()
            ->select('c')
            ->from('AdminSentinelleBundle:Card', 'c')
            ->where('c.animal = 1')
            ->getQuery()
            ->getResult();

        return $this->view(array('tree' => $arrayTree, 'cards' => $arrayCard, 'specific_card' => CardSerialization::serializeCards($specificCard)), Codes::HTTP_OK);
    }

}
