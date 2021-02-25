<?php

namespace Api\ApiBundle\Controller\Admin;

use Admin\SentinelleBundle\Form\Type\CategoryType;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\SentinelleBundle\Entity\Category;
use Admin\SentinelleBundle\Entity\CategoryMedia;
use Api\ApiBundle\Controller\v1\ApiRestController;
use \Admin\SentinelleBundle\Entity\Zone;
use \Admin\SentinelleBundle\Entity\Receiver;

/**
 * Description of CategoryController
 *
 */
class CategoriesController extends ApiRestController
{

    /**
     * Retourne l'arborescence
     *
     * GET /admin/categories
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getCategoriesAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');
        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = array();

        foreach ($trees as $tree) {
            $array = $this->getFormatCategory($tree);
            (!is_null($array)) ? $arrayTree[] = $array : '';
        }

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);

    }

    /**
     * FR : Retourne l'arborescence
     * EN : Returns data of tree
     *
     * GET /admin/categories/{category_id}
     *
     * @param \Admin\SentinelleBundle\Entity\Category $category
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"CategoryDetail"})
     *
     * @ParamConverter("category", class="AdminSentinelleBundle:Category")
     */
    public function getCategoryAction(Category $category)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        return $this->view(array('category' => $category), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne l'arborescence
     * EN : Returns data of tree
     *
     * GET /admin/category/all
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"CategoryDetail"})
     */
    public function getCategoryAllAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = array();

        foreach ($trees as $tree) {
            $array = $this->getFormatCategoryAdmin($tree);
            (!is_null($array)) ? $arrayTree[] = $array : '';
        }

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne l'arborescence de la zone
     * EN : Returns data of tree of zone
     *
     * GET /admin/categories/{zone_id}/all/zone
     *
     * @param \Admin\SentinelleBundle\Entity\Zone $zone
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"CategoryDetail"})
     * @ParamConverter("zone", class="AdminSentinelleBundle:Zone")
     */
    public function getCategoryAllZoneAction(Zone $zone)
    {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');
        $arrayTree = array();
        $array_ok = array();
        foreach ($zone->getLocalities() as $locality) {
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
        foreach ($trees as $tree) {
            $arrayTree[] = $this->getFormatCategoryByZoneAdmin($tree, $zone, $array_ok);
        }

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne l'arborescence d'un destinataire
     * EN : Returns data of tree of receiver
     *
     * GET /admin/categories/{receiver_id}/all/receiver
     *
     * @param \Admin\SentinelleBundle\Entity\Receiver $receiver
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"CategoryDetail"})
     * @ParamConverter("receiver", class="AdminSentinelleBundle:Receiver")
     */
    public function getCategoryAllReceiverAction(Receiver $receiver)
    {
        $this->authorize();

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminSentinelleBundle:Category')->getRootNodes('lft');

        $arrayTree = array();

        foreach ($trees as $tree) {
            $array = $this->getFormatCategoryByReceiverAdmin($tree, $receiver);
            (!is_null($array)) ? $arrayTree[] = $array : '';
        }

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);
    }

    /**
     * FR : Ajoute une categorie
     * EN : Adds a category
     *
     * POST /admin/categories
     *
     * Content-Type: form-data
     *      category[name] = "Animaux"
     *      category[visible] = 1
     *      category[type] = 1
     *      category[parent] = 10
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postCategoryAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $category = new Category();
        $form = $this->createForm(new CategoryType(), $category, array('csrf_protection' => false));

        $form->submit($request);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            $manager->persist($category);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * FR : ajoute des images aux categories
     *
     * GET /admin/category/photo
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCategoryPhotoAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('c')
            ->from('AdminSentinelleBundle:Category', 'c')
            ->orderBy('c.lvl', 'ASC');
        $results = $qb->getQuery()->getResult();
        $utf8 = array(
            '/[áàâãªä]/u' => 'a',
            '/[ÁÀÂÃÄ]/u' => 'A',
            '/[ÍÌÎÏ]/u' => 'I',
            '/[íìîï]/u' => 'i',
            '/[éèêë]/u' => 'e',
            '/[ÉÈÊË]/u' => 'E',
            '/[óòôõºö]/u' => 'o',
            '/[ÓÒÔÕÖ]/u' => 'O',
            '/[úùûü]/u' => 'u',
            '/[ÚÙÛÜ]/u' => 'U',
            '/ç/' => 'c',
            '/Ç/' => 'C',
            '/ñ/' => 'n',
            '/Ñ/' => 'N',
            '/-/' => '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u' => ' ', // Literally a single quote
            '/[“”«»„]/u' => ' ', // Double quote
            '/ /' => '-', // nonbreaking space (equiv. to 0x160)
            '/.png/' => '', // nonbreaking space (equiv. to 0x160)
        );

        $dir = $_SERVER["DOCUMENT_ROOT"] . "/img/icons";
        $dh = opendir($dir);
        $files = array();
        $cat = array();
        while (false !== ($filename = readdir($dh))) {
            if (!in_array($filename, array('.', '..'))) {
                $files[] = strtolower(preg_replace(array_keys($utf8), array_values($utf8), $filename));
            }
        }
        $files = array_combine($files, array_map('strlen', $files));
        asort($files);

        foreach ($results as $category) {
            $name = $category->getName();
            $formated_name = strtolower(preg_replace(array_keys($utf8), array_values($utf8), $name));
            $cat[] = $formated_name;
            foreach ($files as $formated_file => $lenght) {
                if (strpos($formated_name, $formated_file) !== false) {
                    $media = new CategoryMedia();
                    $media->setName($formated_file . ".png");
                    $media->setType(CategoryMedia::TYPE_IMAGE);
                    $media->setState(CategoryMedia::STATE_ACTIVE);
                    $media->setPath("/img/icons/" . $formated_file . ".png");
                    $category->setMedia($media);

                    $manager->persist($media);
                    $manager->persist($category);
                    $manager->flush();
                }
            }
        }

        return $this->view($this->success(), Codes::HTTP_CREATED);
    }

    /**
     * FR : modifie une categorie de la base de donnÃ©e
     * EN : Edit a category of database
     *
     * PUT /admin/categories/{category_id}
     *
     * Content-Type: form-data
     *      category[name] = "Animaux"
     *      category[visible] = 1
     *      category[type] = 1
     *
     * @param Request $request
     * @param Category $category
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("category", class="AdminSentinelleBundle:Category")
     */
    public function putCategoryAction(Request $request, Category $category)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $form = $this->createForm(new CategoryType(), $category, array('csrf_protection' => false, 'method' => 'PUT'));

        $form->submit($request);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            $manager->persist($category);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(true, false), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * FR : supprime une categorie de la base de données (pas de suppression en cascade)
     * EN : removes a category from the database (no cascade delete)
     *
     * DELETE /admin/categories/{category_id}/singlenode
     *
     * @param Category $category
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("category", class="AdminSentinelleBundle:Category")
     */
    public function deleteCategorySinglenodeAction(Category $category)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $this->getDoctrine()->getManager()->getRepository('AdminSentinelleBundle:Category')->removeFromTree($category);

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * FR : supprime une categorie de la base de donnÃ©e (la catÃ©gorie n'est pas rÃ©ellement supprimÃ©e)
     * EN : removes a category from the database (the category is not deleted)
     *
     * DELETE /admin/categories/{category_id}
     *
     * @param Category $category
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("category", class="AdminSentinelleBundle:Category")
     */
    public function deleteCategoryAction(Category $category)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();

        $em->remove($category);
        $em->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

}
