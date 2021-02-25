<?php

namespace Api\ApiBundle\Controller\Admin;

use Admin\SentinelleBundle\Form\Type\CardType;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\CardLabel;
use Admin\SentinelleBundle\Entity\CardLabelContent;
use Api\ApiBundle\Controller\v1\ApiRestController;

/**
 * Description of GroupsController
 *
 */
class CardsController extends ApiRestController {

    /**
     * Add a card
     *
     * POST /admin/cards
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"CardDetail", "LabelDetail"})
     */
    public function postCardAction(Request $request) {
        $this->authorize(null, 'ROLE_ADMIN');

        $card = new Card();
        $form = $this->createForm(new CardType(), $card, array('csrf_protection' => false));

        $form->submit($request);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            $manager->persist($card);
            $manager->flush();

            return $this->view(array('card' => $this->getFormatCard($card)), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }
    
     /**
     * Duplicate a card
     *
     * POST /admin/duplicates/cards
     * 
     * JSON Data:
     *   {
     *       "card":
     *       {
     *           "name":"Check variable"
     *       },
     *       "label":
     *       [
     *           {
     *               "id": "new",
     *               "name": "Test",
     *               "type": "1",
     *               "required": 0,
     *               "allowContent": false
     *           },
     *           {
     *               "id": "new",
     *               "name": "Area",
     *               "type": "0",
     *               "required": 0,
     *               "allowContent": false
     *           },
     *           {
     *               "id": "new",
     *               "name": "Test 2",
     *               "type": "40",
     *               "required": 0,
     *               "allowContent": true,
     *               "contents": 
     *               [
     *                   {
     *                       "id": "",
     *                       "name": "Test 1"
     *                   },
     *                   {
     *                       "id": "",
     *                       "name": "Test 2"
     *                   }
     *               ]
     *           }
     *       ]
     *   }
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"CardDetail", "LabelDetail"})
     */
    public function postDuplicateCardAction(Request $request) {
        $this->authorize(null, 'ROLE_ADMIN');
        $params = $request->request->get('card', false);
        $card = new Card();
        $form = $this->createForm(new CardType($this->container), $card);
            
        $manager = $this->getDoctrine()->getManager();
        $card->setName($params["name"]);
        $card->setVisible(Card::VISIBLE_ON);
        $manager->persist($card);
        $manager->flush();
        $arrayLabels = $request->get('label');
        $manager = $this->getDoctrine()->getManager();
        if (count($card->getLabels())) {
            foreach ($card->getLabels() as $label) {
                $label->setVisible(0);
                $manager->persist($label);
                $manager->flush();
            }
        }
        foreach ($arrayLabels as $label) {
            $em = $this->getDoctrine()->getManager();
            $id = $label["id"];
            if ($label["id"] == "new") {
                $labelObject = new CardLabel();
            } else {
                $repoCategory = $em->getRepository('AdminSentinelleBundle:CardLabel');
                $labelObject = $repoCategory->find($label["id"]);
                if (!is_object($labelObject)) {
                    $labelObject = new CardLabel();
                    $id = "new";
                }
            }
            if (!empty($label['required'])) {
                $labelObject->setRequired(1);
            } else {
                $labelObject->setRequired(0);
            }
            $labelObject->setVisible(1);
            $labelObject->setName($label['name']);
            $labelObject->setType(is_null($label['type']) ? CardLabel::TYPE_STRING : $label['type']);
            if ($id == "new") {
                $labelObject->setCard($card);
            }
            if (count($labelObject->getContents())) {
                foreach ($labelObject->getContents() as $content) {
                    $content->setVisible(0);
                    $manager->persist($content);
                    $manager->flush();
                }
            }
            if ($label['allowContent']) {
                foreach ($label['contents'] as $content) {
                    $idContent = $content["id"];
                    if ($content["id"] == "") {
                        $contentObject = new CardLabelContent();
                    } else {
                        $repoContent = $em->getRepository('AdminSentinelleBundle:CardLabelContent');
                        $contentObject = $repoContent->find($content["id"]);
                        if (!is_object($contentObject)) {
                            $contentObject = new CardLabelContent();
                            $idContent = "new";
                        }
                    }
                    $contentObject->setName($content["name"]);
                    if ($content["name"] != "") {
                        $contentObject->setVisible(1);
                    }
                    $contentObject->setLabel($labelObject);
                    $manager->persist($contentObject);
                    $manager->flush();
                }
            }
            $manager->persist($labelObject);
            $manager->flush();
        }
        return $this->view(array('card' => $this->getFormatCard($card)), Codes::HTTP_CREATED);
    }

    /**
     * Edit a card
     *
     * PUT /admin/cards/{card_id}
     *
     * @param Request $request
     * @param Card $card
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("card", class="AdminSentinelleBundle:Card")
     *
     * @View(serializerGroups={"CardDetail", "LabelDetail"})
     */
    public function putCardAction(Request $request, Card $card) {
        $this->authorize(null, 'ROLE_ADMIN');

        $form = $this->createForm(new CardType(), $card, array('csrf_protection' => false, 'method' => 'PUT'));

        $form->submit($request);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            $manager->persist($card);
            $manager->flush();

            $manager->flush();

            return $this->view(array('card' => $this->getFormatCard($card)), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Returns the data of a precise card
     *
     * GET /admin/cards/{card_id}
     *
     * @param Card $card
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("card", class="AdminSentinelleBundle:Card")
     */
    public function getCardAction(Card $card) {
        $this->authorize(null, 'ROLE_ADMIN');

        return $this->view(array('card' => $this->getFormatCard($card)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les fiches
     * EN : Returns cards
     *
     * GET /admin/cards?limit=10&offset=0&filter=test
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View;
     */
    public function getCardsAction(Request $request) {
        $this->authorize(null, 'ROLE_ADMIN');

        $limit = $request->query->get('limit', 100);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();

        $qb = $manager->createQueryBuilder()->select('c')
                ->from('AdminSentinelleBundle:Card', 'c')
                ->where('c.visible = :visible')
                ->andWhere('c.name LIKE :name')
                ->andWhere('c.animal = 0')
                ->setParameter('name', '%' . strtolower($filter) . '%')
                ->setParameter('visible', Card::VISIBLE_ON)
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->orderBy('c.name', 'ASC');

        $results = $qb->getQuery()->getResult();

        $cards = array();
        foreach ($results as $result) {
            $cards[] = $this->getFormatCard($result);
        }

        return $this->view(array('cards' => $cards), Codes::HTTP_OK);
    }

    /**
     * FR : Supprime une fiche de la BDD
     * EN : Remove a card of database
     *
     * DELETE /admin/cards/{card_id}
     *
     * @param Card $card
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("card", class="AdminSentinelleBundle:Card")
     */
    public function deleteCardAction(Card $card) {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();
        $card->setVisible(Card::VISIBLE_OFF);

        $manager->persist($card);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

}
