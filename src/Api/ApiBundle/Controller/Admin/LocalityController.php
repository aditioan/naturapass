<?php

namespace Api\ApiBundle\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\MainBundle\Component\GeolocationService;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\MainBundle\Entity\Country;
use NaturaPass\UserBundle\Entity\DogBreed;
use NaturaPass\UserBundle\Entity\DogType;
use NaturaPass\UserBundle\Entity\WeaponBrand;
use NaturaPass\UserBundle\Entity\WeaponCalibre;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Api\ApiBundle\Controller\v1\ApiRestController;
use Admin\SentinelleBundle\Entity\Locality;
use Api\ApiBundle\Controller\v2\Serialization\LocalitySerialization;

/**
 * Description of LocalityController
 *
 */
class LocalityController extends ApiRestController
{

    /**
     * FR : Retourne les données d'une localitée
     * EN : Returns datas of a locality
     *
     * GET /admin/localities/{locality_id}
     *
     * @param Locality $locality
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("locality", class="AdminSentinelleBundle:Locality")
     * @View(serializerGroups={"LocalityDetail", "LocalityLess"})
     */
    public function getLocalityAction(Locality $locality)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        return $this->view(array('locality' => $locality), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les animaux
     * EN : Returns localities
     *
     * GET /admin/localities?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LocalityLess"})
     */
    public function getLocalitiesAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');
//        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('l')
            ->from('AdminSentinelleBundle:Locality', 'l')
            ->where('l.name LIKE :name')
            ->orderBy('l.name', 'ASC')
            ->setParameter('name', '%' . $filter . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        return $this->view(array('localities' => LocalitySerialization::serializeLocalitys($results)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les animaux
     * EN : Returns localities
     *
     * GET /admin/localitiy/search?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LocalityLess"})
     */
    public function getLocalitySearchAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');
//        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('l')
            ->from('AdminSentinelleBundle:Locality', 'l')
            ->where('l.name LIKE :name')
            ->orWhere('l.postal_code LIKE :name')
            ->orWhere('l.administrative_area_level_2 LIKE :name')
            ->orderBy('l.name', 'ASC')
            ->setParameter('name', '%' . $filter . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        $results = $qb->getQuery()->getResult();

        return $this->view(array('localities' => LocalitySerialization::serializeLocalitySearchs($results)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les animaux
     * EN : Returns localities
     *
     * GET /admin/localitiy/department/search?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"LocalityLess"})
     */
    public function getLocalityDepartmentSearchAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');
//        $this->authorize();

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('l')
            ->from('AdminSentinelleBundle:Locality', 'l')
            ->where('l.administrative_area_level_2 LIKE :name')
            ->orderBy('l.administrative_area_level_2', 'ASC')
            ->setParameter('name', '%' . $filter . '%')
            ->distinct('l.administrative_area_level_2')
            ->groupBy("l.administrative_area_level_2")
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        $results = $qb->getQuery()->getResult();

        return $this->view(array('departments' => LocalitySerialization::serializeLocalityDepartmentSearchs($results)), Codes::HTTP_OK);
    }

    /**
     * set insee code from csv
     *
     * GET /admin/insee/csv
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"DistributorDetail", "DistributorLess"})
     */
    public function getInseeCsvAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');
        $manager = $this->getDoctrine()->getManager();
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/uploads/new-localities.csv', "r")) !== FALSE)
        {
            $updated = 0;
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE)
            {
                $name = $data[2];
                $level2 = $data[5];
                $level1 = $data[6];
                $postal_code = $data[8];
                $insee = $data[9];

                $locality = $manager->getRepository('AdminSentinelleBundle:Locality')->findOneBy(
                    array(
                        'postal_code' => $postal_code,
                        'administrative_area_level_1' => $level1,
                        'administrative_area_level_2' => $level2,
                        'name' => $name,
                        'insee' => null
                    )
                );
                if ($locality instanceof Locality)
                {
                    $locality->setInsee($insee);
                    $manager->persist($locality);
                    $manager->flush();
                    $updated++;
                }
            }
            if ($updated > 0)
            {
                return $this->view(array('success' => true, 'message' => $updated.' data synched'), Codes::HTTP_OK);
            }
            return $this->view(array('success' => false, 'message' => 'data already sync'), Codes::HTTP_OK);
        }
        return $this->view(array('success' => false, 'message' => 'file uploads/new-localities.csv not found'), Codes::HTTP_NOT_FOUND);
    }

    /**
     * FR : Enregistre les data du fichier excel en BDD
     *
     * GET /admin/localitiy/excel
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"DistributorDetail", "DistributorLess"})
     */
    public function getLocalityExcelAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();
        $errors = array();
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/uploads/localities.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $init = 0;
                $insee = sprintf("%'.05d", $data[$init]);
                $init++;
                $name1 = $data[$init];
                $init++;
                $postal_code = sprintf("%'.05d", $data[$init]);
                $dep = substr($postal_code, 0, 2);
                $init++;
                $name2 = $data[$init];
                if (in_array($dep, array("77", "59"))) {
                    $locality = $manager->getRepository('AdminSentinelleBundle:Locality')->findOneBy(
                        array(
                            'insee' => $insee
                        )
                    );
                    if (!$locality instanceof Locality) {
                        $key = GeolocationService::$apiKey[array_rand(GeolocationService::$apiKey)];
                        $url = 'https://maps.googleapis.com/maps/api/geocode/json?'
                            . http_build_query(
                                array(
                                    'key' => $key,
                                    'address' => utf8_encode($name1),
                                    'components' => "country:FR|locality:" . utf8_encode($name1) . "|postal_code:" . $postal_code,
                                )
                            );

                        $curl = curl_init();
                        curl_setopt_array(
                            $curl, array(
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_FOLLOWLOCATION => false,
                                CURLOPT_SSL_VERIFYPEER => false,
                                CURLOPT_SSLVERSION => 1,
                                CURLOPT_HEADER => false,
                                CURLOPT_CONNECTTIMEOUT => 2,
                                CURLOPT_TIMEOUT => 10,
                                CURLOPT_REFERER => $this->container->getParameter("url_default"),
                                CURLOPT_URL => $url
                            )
                        );
                        $raw = curl_exec($curl);
                        if ($raw && curl_getinfo($curl, CURLINFO_HTTP_CODE) == Codes::HTTP_OK) {
                            $response = json_decode($raw, true);
                            if ($response['status'] == 'OK') {
                                $formatted = array();
                                $geolocation = new Geolocation();
                                $geolocation->setLatitude($response['results'][0]['geometry']['location']['lat']);
                                $geolocation->setLongitude($response['results'][0]['geometry']['location']['lng']);
                                $check_postal_code = false;
                                foreach ($response['results'][0]['address_components'] as $component) {
                                    if (count($component['types'])) {
                                        foreach ($component['types'] as $type) {
                                            if ($type == "postal_code") {
                                                $check_postal_code = true;
                                            }
                                        }
                                    }
                                }
                                if ($check_postal_code) {
                                    $location = $this->getGeolocationService()->transformArray($response, $geolocation);
                                    $locality = $this->getGeolocationService()->findACityWithResponse($location);
                                } else {
                                    $locality = $this->getGeolocationService()->findACity($geolocation, false, true);
                                }
                                if ($locality instanceof Locality) {
                                    $locality->setInsee($insee);
                                    $manager->persist($locality);
                                    $manager->flush();
                                } else {
                                    $errors[] = array(utf8_encode($name2 . "," . $dep . ",France"), 1, $key, $response);
                                }
                            } else {
                                $errors[] = array(utf8_encode($name2 . "," . $dep . ",France"), 2, $key, $response);
                            }
                        } else {
                            $errors[] = array(utf8_encode($name2 . "," . $dep . ",France"), 3);
                        }
                    }
                }
            }
            return $this->view(array_merge($this->success(), $errors), Codes::HTTP_OK);
        }
    }

    /**
     * FR : Supprime une localitée de la BDD
     * EN : Remove an locality of database
     *
     * DELETE /admin/localities/{locality_id}
     *
     * @param Locality $locality
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("locality", class="AdminSentinelleBundle:Locality")
     */
    public
    function deleteLocalityAction(Locality $locality)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($locality);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

    public function getImportCountryAction() {
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject('Name_s_Countries.xlsx');
        foreach ($phpExcelObject->getWorksheetIterator() as $worksheet) {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                $i=0;
                foreach ($cellIterator as $cell) {
                    $i++;
                    if (!is_null($cell) && $i<2) {
                        echo $cell->getCalculatedValue(),'<br/>';
                        $tool = new Country();
                        $tool->setName($cell->getCalculatedValue());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($tool);
                        $em->flush();
                    }
                }
            }
        }
        return true;
    }

    public function getImportDogAction() {
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject('dog_s_list.xlsx');
        foreach ($phpExcelObject->getWorksheetIterator() as $worksheet) {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                $i=0;
                foreach ($cellIterator as $cell) {
                    $i++;
                    if (!is_null($cell) && $i<2) {
                        echo $cell->getCalculatedValue(),'<br/>';
                        $tool = new DogBreed();
                        $tool->setName($cell->getCalculatedValue());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($tool);
                        $em->flush();
                    }
                }
            }
        }
        return true;
    }

    public function getImportDogTypeAction() {
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject('dogs_type.xlsx');
        foreach ($phpExcelObject->getWorksheetIterator() as $worksheet) {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                $i=0;
                foreach ($cellIterator as $cell) {
                    $i++;
                    if (!is_null($cell) && $i<2) {
                        echo $cell->getCalculatedValue(),'<br/>';
                        $tool = new DogType();
                        $tool->setName($cell->getCalculatedValue());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($tool);
                        $em->flush();
                    }
                }
            }
        }
        return true;
    }

    public function getImportWeaponBrandAction() {
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject('weapons__list.xlsx');
        foreach ($phpExcelObject->getWorksheetIterator() as $worksheet) {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                $i=0;
                foreach ($cellIterator as $cell) {
                    $i++;
                    if (!is_null($cell) && $i<2) {
                        echo $cell->getCalculatedValue(),'<br/>';
                        $tool = new WeaponBrand();
                        $tool->setName($cell->getCalculatedValue());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($tool);
                        $em->flush();
                    }
                }
            }
        }
        return true;
    }

    public function getImportWeaponCalibreAction() {
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject('calibres__list.xlsx');
        foreach ($phpExcelObject->getWorksheetIterator() as $worksheet) {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
                $i=0;
                foreach ($cellIterator as $cell) {
                    $i++;
                    if (!is_null($cell) && $i<2) {
                        echo $cell->getCalculatedValue(),'<br/>';
                        $tool = new WeaponCalibre();
                        $tool->setName($cell->getCalculatedValue());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($tool);
                        $em->flush();
                    }
                }
            }
        }
        return true;
    }



}

