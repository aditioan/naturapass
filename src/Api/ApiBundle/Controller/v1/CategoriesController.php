<?php

namespace Api\ApiBundle\Controller\v1;

use Admin\SentinelleBundle\Form\Type\CategoryType;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\SentinelleBundle\Entity\Category;
use Api\ApiBundle\Controller\v1\ApiRestController;
use \Admin\SentinelleBundle\Entity\Zone;
use \Admin\SentinelleBundle\Entity\Receiver;
use \NaturaPass\PublicationBundle\Entity\Publication;

class CategoriesController extends ApiRestController {

    /**
     * FR : Retourne l'arborescence de la ville de la publication
     * EN : Returns data of tree of locality of publication
     *
     * GET /category/{publication_id}/all
     *
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return \FOS\RestBundle\View\View
     *
     * Type of Card
     *
     * STRING = 0;
     * TEXT = 1;
     * INT = 10;
     * FLOAT = 11;
     * DATE = 20;
     *
     * @View(serializerGroups={"CategoryDetail"})
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getCategoryAllAction(Publication $publication) {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');
        $arrayTree = array();
        $array_ok = array();
        $locality = $publication->getLocality();
        if (is_object($locality)) {
            foreach ($locality->getReceivers() as $receiver) {
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
        $zone = (is_object($locality) && is_object($locality->getZone())) ? $locality->getZone() : null;
        foreach ($trees as $tree) {
            $arrayTree[] = $this->getFormatPublicationCategorieTree($tree, $zone, $array_ok);
        }
        $arrayCard = array();
        $cards = $this->getDoctrine()->getManager()
                ->createQueryBuilder()
                ->select('c')
                ->from('AdminSentinelleBundle:Card', 'c')
                ->where('c.animal = 0')
                ->getQuery()
                ->getResult();
        foreach ($cards as $card) {
            $arrayCard[$card->getId()] = $this->getFormatCard($card);
        }

        return $this->view(array('tree' => $arrayTree, 'cards' => $arrayCard), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne l'arborescence générale
     * EN : Returns data of general tree
     *
     * GET /category
     *
     * @return \FOS\RestBundle\View\View
     *
     * Type of Card
     *
     * STRING = 0;
     * TEXT = 1;
     * INT = 10;
     * FLOAT = 11;
     * DATE = 20;
     *
     * @View(serializerGroups={"CategoryDetail"})
     */
    public function getCategoryAction() {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');
        $arrayTree = array();
        foreach ($trees as $tree) {
            $arrayTree[] = $this->getFormatCategory($tree, true);
        }
        $arrayCard = array();
        $cards = $this->getDoctrine()->getManager()
                ->createQueryBuilder()
                ->select('c')
                ->from('AdminSentinelleBundle:Card', 'c')
                ->where('c.animal = 0')
                ->getQuery()
                ->getResult();
        foreach ($cards as $card) {
            $arrayCard[$card->getId()] = $this->getFormatCard($card);
        }

        return $this->view(array('tree' => $arrayTree, 'cards' => $arrayCard), Codes::HTTP_OK);
    }

}
