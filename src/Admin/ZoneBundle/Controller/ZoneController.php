<?php

namespace Admin\ZoneBundle\Controller;

use Admin\SentinelleBundle\Entity\CardCategoryZone;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Admin\SentinelleBundle\Entity\Zone;
use Admin\ZoneBundle\Form\Handler\ZoneHandler;
use Admin\ZoneBundle\Form\Type\ZoneType;

class ZoneController extends Controller
{

    /**
     *
     * @param \Admin\SentinelleBundle\Entity\Zone $zone
     * @return type
     *
     * @ParamConverter("zone", class="AdminSentinelleBundle:Zone")
     */
    public function treeAction($zone, Request $request)
    {
        $arrayTree = json_decode($request->get('tree'), true);
        if (count($arrayTree)) {
            $em = $this->getDoctrine()->getManager();
            $repoCategory = $em->getRepository('AdminSentinelleBundle:Category');
            foreach ($zone->getCards() as $cardCategoryZone) {
                $em->remove($cardCategoryZone);
            }
            $em->flush();
            $repoCategory->setTreeZoneCardModel($zone, $arrayTree);
        }
        return $this->render('AdminZoneBundle:Zone:angular.tree_zone.html.twig', array(
            'zone' => $zone
        ));
    }

    public function addAction(Request $request)
    {
        $form = $this->createForm(new ZoneType(), new Zone());
        $handler = new ZoneHandler($form, $request, $this->getDoctrine()->getManager());

        if ($zoneHandler = $handler->process()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($zoneHandler->getLocalities() as $locality) {
                foreach ($locality->getReceivers() as $receiver) {
                    foreach ($receiver->getReceiverrights() as $receiverRight) {
                        $category = $receiverRight->getCategory();
                        $card = $category->getCard();
                        if (!is_null($card)) {
                            $cardCategoryByZone = new CardCategoryZone();
                            $cardCategoryByZone->setCard($card);
                            $cardCategoryByZone->setZone($zoneHandler);
                            $cardCategoryByZone->setCategory($category);
                            $cardCategoryByZone->setVisible(CardCategoryZone::VISIBLE_ON);
                            $em->persist($cardCategoryByZone);
                            $em->flush();
                        }
                    }
                }
            }
            return new RedirectResponse($this->get('router')->generate('admin_entity_zone_tree', array('zone' => $zoneHandler->getId())));
        } else {
            return $this->render('AdminZoneBundle:Zone:angular.add.html.twig', array(
                'form' => $form->createView(),
                'add' => 1,
            ));
        }
    }

    /**
     *
     * @param \Admin\SentinelleBundle\Entity\Zone $zone
     * @return type
     *
     * @ParamConverter("zone", class="AdminSentinelleBundle:Zone")
     */
    public function editAction($zone, Request $request)
    {
        $form = $this->createForm(new ZoneType(), $zone);
        $handler = new ZoneHandler($form, $request, $this->getDoctrine()->getManager());

        if ($zoneHandler = $handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_entity_zone_tree', array('zone' => $zoneHandler->getId())));
        } else {
            return $this->render('AdminZoneBundle:Zone:angular.add.html.twig', array(
                'id' => $zone->getId(),
                'form' => $form->createView(),
                'add' => 0,
            ));
        }
    }

    public function listAction()
    {
        return $this->render('AdminZoneBundle:Zone:angular.index.html.twig');
    }

}
