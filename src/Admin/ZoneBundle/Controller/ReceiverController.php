<?php

namespace Admin\ZoneBundle\Controller;

use Admin\SentinelleBundle\Entity\Locality;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Admin\SentinelleBundle\Entity\Receiver;
use Admin\ZoneBundle\Form\Handler\ReceiverHandler;
use Admin\ZoneBundle\Form\Type\ReceiverType;

class ReceiverController extends Controller
{

    /**
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return type
     *
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function treeAction($receiver, Request $request)
    {
        $arrayTree = json_decode($request->get('tree'), true);
        if (count($arrayTree)) {
            $em = $this->getDoctrine()->getManager();
            $repoCategory = $em->getRepository('AdminSentinelleBundle:Category');
            foreach ($receiver->getReceiverrights() as $receiverright) {
                $em->remove($receiverright);
            }
            $em->flush();
            $repoCategory->setReceiverRightModel($receiver, $arrayTree);
        }
        return $this->render('AdminZoneBundle:Receiver:angular.tree_receiver.html.twig', array(
            'receiver' => $receiver
        ));
    }

    public function addAction(Request $request)
    {
        $form = $this->createForm(new ReceiverType($this->container), new Receiver());
        $handler = new ReceiverHandler($form, $request, $this->getDoctrine()->getManager());

        if ($receiverHandler = $handler->process()) {
            $arrayCities = json_decode($request->get('cities'), true);
            $em = $this->getDoctrine()->getManager();
            foreach ($arrayCities as $citie) {
                $locality = $em->getRepository('AdminSentinelleBundle:Locality')->find($citie["id"]);
                if (is_object($locality)) {
                    $locality->addReceiver($receiverHandler);
                    $em->persist($locality);
                }
            }
            $arrayDepartments = json_decode($request->get('departments'), true);
            $em = $this->getDoctrine()->getManager();
            foreach ($arrayDepartments as $department) {
                $localities = $em->getRepository('AdminSentinelleBundle:Locality')->findBy(array(
                    'administrative_area_level_2' => $department["text"],
                ));
                foreach ($localities as $locality) {
                    if (is_object($locality) && !$locality->getReceivers()->contains($receiverHandler)) {
                        $locality->addReceiver($receiverHandler);
                        $em->persist($locality);
                    }
                }
            }
            $arrayGroups = json_decode($request->get('groups'), true);
            foreach ($arrayGroups as $group) {
                $groupObject = $em->getRepository('NaturaPassGroupBundle:Group')->find($group["id"]);
                if (is_object($groupObject)) {
                    $receiverHandler->addGroup($groupObject);
                }
            }
            $arrayUsers = json_decode($request->get('users'), true);
            foreach ($arrayUsers as $user) {
                $userObject = $em->getRepository('NaturaPassUserBundle:User')->find($user["id"]);
                if (is_object($userObject)) {
                    $userObject->addRole("ROLE_BACKOFFICE");
                    $em->persist($userObject);
                    $receiverHandler->addUser($userObject);
                }
            }
            $em->persist($receiverHandler);
            $em->persist($receiverHandler);
            $em->flush();
            return new RedirectResponse($this->get('router')->generate('admin_entity_receiver_tree', array('receiver' => $receiverHandler->getId())));
        } else {
            return $this->render('AdminZoneBundle:Receiver:angular.add.html.twig', array(
                'form' => $form->createView(),
                'add' => 1,
            ));
        }
    }

    /**
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return type
     *
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function editAction($receiver, Request $request)
    {
        $form = $this->createForm(new ReceiverType($this->container), $receiver);
        $handler = new ReceiverHandler($form, $request, $this->getDoctrine()->getManager());

        if ($receiverHandler = $handler->process()) {
            $arrayCities = json_decode($request->get('cities'), true);
            $em = $this->getDoctrine()->getManager();
            $receiverHandler->removeAllLocalities();
            $em->persist($receiverHandler);
            $em->flush();

	    //open when you want to add all cites to a FDC start with prefix postal_code
            //start
            /*$manager = $this->getDoctrine()->getManager();
            $qb = $manager->createQueryBuilder()->select('l')
                ->from('AdminSentinelleBundle:Locality', 'l')
                ->where('l.postal_code LIKE :code')
                ->orderBy('l.id', 'ASC')
                ->setParameter('code', '21%');

            $results = $qb->getQuery()->getResult();
            $arrayCities = array();
            foreach ($results as $cites) {
                $arrayCities[] = array(
                    'id' => $cites->getId(),
                );
            }*/
            //end

            foreach ($arrayCities as $citie) {
                $locality = $em->getRepository('AdminSentinelleBundle:Locality')->find($citie["id"]);
                if (is_object($locality)) {
                    $locality->addReceiver($receiverHandler);
                    $em->persist($locality);
                }
            }
            $arrayDepartments = json_decode($request->get('departments'), true);
            $em = $this->getDoctrine()->getManager();
            foreach ($arrayDepartments as $department) {
                $localities = $em->getRepository('AdminSentinelleBundle:Locality')->findBy(array(
                    'administrative_area_level_2' => $department["text"],
                ));
                foreach ($localities as $locality) {
                    if (is_object($locality) && !$locality->getReceivers()->contains($receiverHandler)) {
                        $locality->addReceiver($receiverHandler);
                        $em->persist($locality);
                    }
                }
            }
            $arrayGroups = json_decode($request->get('groups'), true);
            $receiverHandler->removeAllGroups();
            foreach ($arrayGroups as $group) {
                $groupObject = $em->getRepository('NaturaPassGroupBundle:Group')->find($group["id"]);
                if (is_object($groupObject)) {
                    $receiverHandler->addGroup($groupObject);
                }
            }
            $arrayUsers = json_decode($request->get('users'), true);
            $receiverHandler->removeAllUsers($em);
            foreach ($arrayUsers as $user) {
                $userObject = $em->getRepository('NaturaPassUserBundle:User')->find($user["id"]);
                if (is_object($userObject)) {
                    $userObject->addRole("ROLE_BACKOFFICE");
                    $em->persist($userObject);
                    $receiverHandler->addUser($userObject);
                }
            }
            $em->persist($receiverHandler);
            $em->flush();
            return new RedirectResponse($this->get('router')->generate('admin_entity_receiver_tree', array('receiver' => $receiverHandler->getId())));
        } else {
            return $this->render('AdminZoneBundle:Receiver:angular.add.html.twig', array(
                'id' => $receiver->getId(),
                'form' => $form->createView(),
                'add' => 0,
            ));
        }
    }

    public function listAction()
    {
        return $this->render('AdminZoneBundle:Receiver:angular.index.html.twig');
    }

    public function importAction() {
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject('cities.csv');
        foreach ($phpExcelObject->getWorksheetIterator() as $worksheet) {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set                
                $i=0;
                foreach ($cellIterator as $cell) {
                    $i++;
                    if (!is_null($cell)) {
                    echo $cell->getCoordinate() , ' - ' , $cell->getCalculatedValue();
                    if ($i==1){
                        $tool = new Locality();
                        $tool->setName($cell->getCalculatedValue());
                    }
                    if ($i==2){
                        $tool->setPostal_code($cell->getCalculatedValue());
                        $tool->setAdministrativeAreaLevel1("Bourgogne");
                        $tool->setAdministrativeAreaLevel2("Côte d'Or");
                        $tool->setCountry("France");
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($tool);
                        $em->flush();
                        echo '<br/>';
                        $i=0;
                    }
                    }
                }
            }
        }
        return true;
    }

}
