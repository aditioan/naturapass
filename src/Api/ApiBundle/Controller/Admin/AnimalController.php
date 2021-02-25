<?php

namespace Api\ApiBundle\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\AnimalBundle\Entity\Animal;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Api\ApiBundle\Controller\v1\ApiRestController;
use Admin\AnimalBundle\Entity\AnimalMedia;

/**
 * Description of GroupsController
 *
 */
class AnimalController extends ApiRestController
{

    /**
     * FR : Retourne les données d'un animal
     * EN : Returns datas of an animal
     *
     * GET /admin/animals/{animal_id}
     *
     * @param Animal $animal
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("animal", class="AdminAnimalBundle:Animal")
     * @View(serializerGroups={"AnimalDetail", "AnimalLess"})
     */
    public function getAnimalAction(Animal $animal)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        return $this->view(array('animal' => $animal), Codes::HTTP_OK);
    }

    /**
     * FR : Enregistre les data du fichier excel en BDD
     *
     * GET /admin/animal/excel
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getAnimalExcelAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/uploads/animals.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $parentName = ucfirst($data[0]);
                if (in_array($parentName, array("Mammifères", "Oiseaux"))) {
                    $name = ucfirst($data[1]);
                    $em = $this->getDoctrine()->getManager();
                    $parent = $em->getRepository('AdminAnimalBundle:Animal')->findOneBy(array("name_fr" => $parentName));
                    if (!is_object($parent)) {
                        $parent = new Animal();
                        $parent->setName_fr($parentName);
                        $parent->setParent(null);
                        $em->persist($parent);
                        $em->flush();
                    }
                    $child = $em->getRepository('AdminAnimalBundle:Animal')->findOneBy(array("name_fr" => $name));
                    if (!is_object($child)) {
                        $child = new Animal();
                        $child->setName_fr($name);
                        $child->setParent($parent);
                        $em->persist($child);
                        $em->flush();
                    }
                }
            }
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les animaux
     * EN : Returns animals
     *
     * GET /admin/animals?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"AnimalLess"})
     */
    public function getAnimalsAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');
//        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('a')
            ->from('AdminAnimalBundle:Animal', 'a')
            ->where('a.name_fr LIKE :name_fr')
            ->orderBy('a.name_fr', 'ASC')
            ->setParameter('name_fr', '%' . $filter . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        $aReturn = array();
        foreach ($results as $animal) {
            $aReturn[] = array(
                'id' => 'new',
                'nodes' => array(),
                'title' => $animal->getName_fr(),
                'visible' => 1,
                'search' => 0
            );
        }

        return $this->view(array('animals' => $aReturn), Codes::HTTP_OK);


        $this->authorize(null, 'ROLE_ADMIN');
    }

    /**
     * FR : Retourne l'arborescence
     * EN : Returns data of tree
     *
     * GET /admins/category/all
     *
     * @return array
     *
     * @View(serializerGroups={"AnimalLess"})
     */
    public function getAnimalAllAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminAnimalBundle:Animal')->getRootNodes('lft');

        $arrayTree = array();

        foreach ($trees as $tree) {
            $arrayTree[] = $this->getFormatAnimalAdmin($tree);
        }

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne l'arborescence
     * EN : Returns data of tree
     *
     * GET /admins/category/all/tree
     *
     * @return array
     *
     * @View(serializerGroups={"AnimalLess"})
     */
    public function getAnimalAllTreeAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $trees = $em->getRepository('AdminAnimalBundle:Animal')->getRootNodes('lft');

        $arrayTree = array();

        foreach ($trees as $tree) {
            $arrayTree[] = $this->getFormatAnimalTreeAdmin($tree);
        }

        return $this->view(array('tree' => $arrayTree), Codes::HTTP_OK);
    }

    /**
     * FR : ajoute des images aux animaux
     *
     * GET /admin/animal/photo
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getAnimalPhotoAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('a')
            ->from('AdminAnimalBundle:Animal', 'a')
            ->orderBy('a.lvl', 'ASC');
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

        foreach ($results as $animal) {
            $name = $animal->getName_fr();
            $formated_name = strtolower(preg_replace(array_keys($utf8), array_values($utf8), $name));
            $cat[] = $formated_name;
            foreach ($files as $formated_file => $lenght) {
                if (strpos($formated_name, $formated_file) !== false) {
                    $media = $manager->getRepository('AdminAnimalBundle:AnimalMedia')->findOneBy(array("name" => $formated_file . ".png"));
                    if (!is_object($media)) {
                        $media = new AnimalMedia();
                        $media->setName($formated_file . ".png");
                        $media->setType(AnimalMedia::TYPE_IMAGE);
                        $media->setState(AnimalMedia::STATE_ACTIVE);
                        $media->setPath("/img/icons/" . $formated_file . ".png");
                        $manager->persist($media);
                    }
                    $animal->setMedia($media);
                    $manager->persist($animal);
                    $manager->flush();
                }
            }
        }

        return $this->view($this->success(), Codes::HTTP_CREATED);
    }

    /**
     * FR : Supprime une marque de la BDD
     * EN : Remove an animal of database
     *
     * DELETE /admin/animals/{animal_id}
     *
     * @param Animal $animal
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("animal", class="AdminAnimalBundle:Animal")
     */
    public function deleteAnimalAction(Animal $animal)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($animal);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

}
