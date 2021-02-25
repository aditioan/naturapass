<?php

namespace Admin\SentinelleBundle\Controller;

use Admin\SentinelleBundle\Entity\CardLabelContent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Admin\SentinelleBundle\Form\Handler\CardHandler;
use Admin\SentinelleBundle\Form\Type\CardType;
use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\CardLabel;

class CardController extends Controller
{

    public function addAction(Request $request)
    {
        $params = $request->request->get('card', false);
        $card = new Card();
        $form = $this->createForm(new CardType($this->container), $card);
        if (!empty($params) && $params["name"]) {
            $manager = $this->getDoctrine()->getManager();
            $card->setName($params["name"]);
            $card->setVisible(Card::VISIBLE_ON);
            $manager->persist($card);
            $manager->flush();
//            $arrayLabels = $request->get('label');
            $arrayLabeljsons = $request->get('labeljson');
            $this->setLabeljsons($card, $arrayLabeljsons);
            $url = $this->container->get('router')->generate('admin_sentinelle_card_list');
            $redirectResponse = new RedirectResponse($url);
            return $redirectResponse;
        } else {
            return $this->render('AdminSentinelleBundle:Card:angular.add.html.twig', array(
                'form' => $form->createView(),
                'add' => 1,
            ));
        }
    }

    public function setLabels($cardValid, $arrayLabels)
    {
        $manager = $this->getDoctrine()->getManager();
        if (count($cardValid->getLabels())) {
            foreach ($cardValid->getLabels() as $label) {
                $label->setVisible(0);
                $manager->persist($label);
                $manager->flush();
            }
        }
        foreach ($arrayLabels['id'] as $index => $id) {
            if ($id == "new") {
                $label = new CardLabel();
            } else {
                $em = $this->getDoctrine()->getManager();
                $repoCategory = $em->getRepository('AdminSentinelleBundle:CardLabel');
                $label = $repoCategory->find($id);
                if (!is_object($label)) {
                    $label = new CardLabel();
                    $id = "new";
                }
            }
            if (!empty($arrayLabels['required'][$index])) {
                $label->setRequired(1);
            } else {
                $label->setRequired(0);
            }
            $label->setVisible(1);
            $label->setName($arrayLabels['name'][$index]);
            $label->setType($arrayLabels['type'][$index]);
            if ($id == "new") {
                $label->setCard($cardValid);
            }
            $manager->persist($label);
            $manager->flush();
        }
    }

    public function setLabeljsons($cardValid, $arrayLabels)
    {
        $manager = $this->getDoctrine()->getManager();
        if (count($cardValid->getLabels())) {
            foreach ($cardValid->getLabels() as $label) {
                $label->setVisible(0);
                $manager->persist($label);
                $manager->flush();
            }
        }

        foreach ($arrayLabels as $labeljson) {
            $label = json_decode($labeljson, true);
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
                $labelObject->setCard($cardValid);
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
    }

    /**
     *
     * @param \Admin\SentinelleBundle\Entity\Card $card
     * @return type
     *
     * @ParamConverter("card", class="AdminSentinelleBundle:Card")
     */
    public function editAction($card, Request $request)
    {
        $params = $request->request->get('card', false);
        $form = $this->createForm(new CardType($this->container), $card);

        if (!empty($params) && $params["name"]) {
            $manager = $this->getDoctrine()->getManager();
            $card->setName($params["name"]);
            $card->setVisible(Card::VISIBLE_ON);
            $manager->persist($card);
            $manager->flush();
//            $arrayLabels = $request->get('label');
            $arrayLabeljsons = $request->get('labeljson');
            $this->setLabeljsons($card, $arrayLabeljsons);
            $url = $this->container->get('router')->generate('admin_sentinelle_card_list');
            $redirectResponse = new RedirectResponse($url);
            return $redirectResponse;
        } else {
            return $this->render('AdminSentinelleBundle:Card:angular.add.html.twig', array(
                'id' => $card->getId(),
                'form' => $form->createView(),
                'add' => 0,
            ));
        }
    }

    /**
     *
     * @param \Admin\SentinelleBundle\Entity\Card $card
     * @return type
     *
     * @ParamConverter("card", class="AdminSentinelleBundle:Card")
     */
    public function orderAction($card, Request $request)
    {
        $params = $request->request->get('order', false);
        if (!empty($params)) {
            $manager = $this->getDoctrine()->getManager();
            $repoCardLabel = $manager->getRepository('AdminSentinelleBundle:CardLabel');
            foreach ($params as $id_cardlabel => $order) {
                $order = intval($order);
                $cardLabel = $repoCardLabel->find($id_cardlabel);
                $cardLabel->setOrder($order);
                $manager->persist($cardLabel);
                $manager->flush();
            }
            $url = $this->container->get('router')->generate('admin_sentinelle_card_list');
            $redirectResponse = new RedirectResponse($url);
            return $redirectResponse;
        } else {
            return $this->render('AdminSentinelleBundle:Card:angular.order.html.twig', array(
                'card' => $card,
            ));
        }
    }

    public function listAction()
    {
        return $this->render('AdminSentinelleBundle:Card:angular.index.html.twig');
    }

}
