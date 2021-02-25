<?php

namespace Api\ApiBundle\Controller\Admin;

use Admin\DistributorBundle\Entity\Brand;
use Doctrine\Common\Collections\ArrayCollection;
use Admin\DistributorBundle\Entity\Distributor;
use Api\ApiBundle\Controller\v1\ApiRestController;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\MainBundle\Entity\Geolocation;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DistributorController extends ApiRestController
{

    /**
     * FR : Retourne les données d'un distributeur
     * EN : Returns datas of a distributor
     *
     * GET /admin/distributors/{distributor_id}
     *
     * @param Distributor $distributor
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("distributor", class="AdminDistributorBundle:Distributor")
     * @View(serializerGroups={"DistributorDetail", "DistributorLess"})
     */
    public function getDistributorAction(Distributor $distributor)
    {
        return $this->view(array('distributor' => $distributor), Codes::HTTP_OK);
    }

    /**
     * FR : Enregistre les data du fichier excel en BDD
     *
     * GET /admin/distributor/excel
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"DistributorDetail", "DistributorLess"})
     */
    public function getDistributorExcelAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('AdminDistributorBundle:Brand', 'b')
            ->orderBy('b.created', 'DESC');
        $results = $qb->getQuery()->getResult();
        $brand = null;
        foreach ($results as $result) {
            $brand = $result;
        }
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/uploads/distributeurs.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $init = 0;
                $valNom = $data[$init];
                $init++;
                $valAdresse = $data[$init];
                $init++;
                $valAdresse2 = $data[$init];
                $init++;
                $valCp = $data[$init];
                $init++;
                $valVille = $data[$init];
                $init++;
                $valTel = $data[$init];
                $init++;
                $valMail = $data[$init];
                $init++;
                $qbDistrib = $manager->createQueryBuilder()->select('d')
                    ->from('AdminDistributorBundle:Distributor', 'd')
                    ->where('d.name LIKE :name')
                    ->setParameter('name', $valNom);
                $resultDistribs = $qbDistrib->getQuery()->getResult();
                if (count($resultDistribs) == 0) {
                    $distributeur = new Distributor();
                    $distributeur->setName($valNom);
                    $distributeur->setTelephone($valTel);
                    $distributeur->setEmail($valMail);
                    $distributeur->setAddress($valAdresse . (($valAdresse2 != '') ? ' ' . $valAdresse2 : ''));
                    $distributeur->setCp($valCp);
                    $distributeur->setCity($valVille);
                    $distributeur->addBrand($brand);

                    $address = $valAdresse . (($valAdresse2 != '') ? ' ' . $valAdresse2 : '') . ', ' . $valCp . ' ' . $valVille; // Google HQ
                    $prepAddr = str_replace(' ', '+', $address);
                    $geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
                    $output = json_decode($geocode);
                    if (count($output->results)) {
                        $latitude = $output->results[0]->geometry->location->lat;
                        $longitude = $output->results[0]->geometry->location->lng;
                    } else {
                        $latitude = 0;
                        $longitude = 0;
                    }

                    $geoloc = new Geolocation();
                    $geoloc->setAddress($address);
                    $geoloc->setLatitude($latitude);
                    $geoloc->setLongitude($longitude);

                    $distributeur->setGeolocation($geoloc);

                    $em->persist($distributeur);
                    $em->flush();
                }
            }
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * FR : Enregistre les data du fichier excel en BDD
     *
     * GET /admin/distributor/excel/mary
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"DistributorDetail", "DistributorLess"})
     */
    public function getDistributorExcelMaryAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('AdminDistributorBundle:Brand', 'b')
            ->orderBy('b.created', 'ASC');
        $results = $qb->getQuery()->getResult();
        $brand = null;
        foreach ($results as $result) {
            $brand = $result;
        }
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/uploads/mary_arm.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $init = 0;
                $valNom = utf8_encode($data[$init]);
                $init++;
                $valCp = utf8_encode($data[$init]);
                $init++;
                $valVille = utf8_encode($data[$init]);
                $init++;
                $valPays = utf8_encode($data[$init]);
                $init++;
                $valTel = utf8_encode($data[$init]);
                $init++;
                $init++;
                $valNumero = utf8_encode($data[$init]);
                $init++;
                $init++;
                $valAdresse = utf8_encode($data[$init]);
                $qbDistrib = $manager->createQueryBuilder()->select('d')
                    ->from('AdminDistributorBundle:Distributor', 'd')
                    ->where('d.name LIKE :name')
                    ->setParameter('name', $valNom);
                $resultDistribs = $qbDistrib->getQuery()->getResult();
                if (count($resultDistribs) == 0) {
                    $distributeur = new Distributor();
                    $distributeur->setName($valNom);
                    $distributeur->setTelephone($valTel);
                    $distributeur->setEmail("");
                    $distributeur->setAddress((($valNumero != '') ? $valNumero . " " : '') . $valAdresse);
                    $distributeur->setCp($valCp);
                    $distributeur->setCity($valVille);
                    $distributeur->addBrand($brand);

                    $address = (($valNumero != '') ? $valNumero . " " : '') . $valAdresse . ', ' . $valCp . ' ' . $valVille; // Google HQ
                    $prepAddr = str_replace(' ', '+', $address);
                    $geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
                    $output = json_decode($geocode);
                    if (count($output->results)) {
                        $latitude = $output->results[0]->geometry->location->lat;
                        $longitude = $output->results[0]->geometry->location->lng;
                    } else {
                        $latitude = 0;
                        $longitude = 0;
                    }

                    $geoloc = new Geolocation();
                    $geoloc->setAddress($address);
                    $geoloc->setLatitude($latitude);
                    $geoloc->setLongitude($longitude);

                    $distributeur->setGeolocation($geoloc);

                    $em->persist($distributeur);
                    $em->flush();
                } else {
                    $distributor = $resultDistribs[0];
                    if (!$distributor->getBrands()->contains($brand)) {
                        $distributor->addBrand($brand);
                        $em->persist($distributeur);
                        $em->flush();
                    }
                }
            }
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }


    /**
     * FR : Enregistre les data du fichier excel en BDD
     *
     * GET /admin/distributor/excel/browning
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"DistributorDetail", "DistributorLess"})
     */
    public function getDistributorExcelBrowningAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $manager = $this->getDoctrine()->getManager();
        $brand = $em->getRepository('AdminDistributorBundle:Brand')->findOneBy(array("name" => "Browning"));
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/uploads/browning.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $init = 0;
                $valNom = utf8_encode($data[$init]);
                $init++;
                $valAdresse = utf8_encode($data[$init]);
                $init++;
                $valAdresse2 = utf8_encode($data[$init]);
                $init++;
                $valAdresse3 = utf8_encode($data[$init]);
                $qbDistrib = $manager->createQueryBuilder()->select('d')
                    ->from('AdminDistributorBundle:Distributor', 'd')
                    ->where('d.name LIKE :name')
                    ->setParameter('name', $valNom);
                $resultDistribs = $qbDistrib->getQuery()->getResult();
                if (count($resultDistribs) == 0) {
                    $distributeur = new Distributor();
                    $distributeur->setName($valNom);
                    $distributeur->setAddress((($valAdresse != '') ? $valAdresse : '') . (($valAdresse2 != '') ? ", " . $valAdresse2 : '') . (($valAdresse3 != '') ? ", " . $valAdresse3 : ''));
                    if ($valAdresse3 != "France" && $valAdresse3 != "FRANCE") {
                        $cp = substr($valAdresse3, 0, 5);
                        $ville = str_replace($cp, "", $valAdresse3);
                    } else {
                        $cp = substr($valAdresse2, 0, 5);
                        $ville = str_replace($cp, "", $valAdresse2);
                    }
                    $distributeur->setCp($cp);
                    $distributeur->setCity($ville);
                    $distributeur->addBrand($brand);

                    $address = (($valAdresse != '') ? $valAdresse . ", " : '') . (($valAdresse2 != '') ? $valAdresse2 . ", " : '') . (($valAdresse3 != '') ? $valAdresse3 . " " : ''); // Google HQ
                    $prepAddr = str_replace(' ', '+', $address);
                    $geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
                    $output = json_decode($geocode);
                    if (count($output->results)) {
                        $latitude = $output->results[0]->geometry->location->lat;
                        $longitude = $output->results[0]->geometry->location->lng;
                    } else {
                        $latitude = 0;
                        $longitude = 0;
                    }

                    $geoloc = new Geolocation();
                    $geoloc->setAddress($address);
                    $geoloc->setLatitude($latitude);
                    $geoloc->setLongitude($longitude);

                    $distributeur->setGeolocation($geoloc);

                    $em->persist($distributeur);
                    $em->flush();
                } else {
                    $distributor = $resultDistribs[0];
                    if (!$distributor->getBrands()->contains($brand)) {
                        $distributor->addBrand($brand);
                        $em->persist($distributeur);
                        $em->flush();
                    }
                }
            }
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }


    /**
     * FR : Enregistre les data du fichier excel en BDD
     *
     * GET /admin/distributor/excel/verney
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"DistributorDetail", "DistributorLess"})
     */
    public function getDistributorExcelVerneyAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('AdminDistributorBundle:Brand', 'b')
            ->where('b.name LIKE :name')
            ->orderBy('b.created', 'ASC')
            ->setParameter('name', "Verney Carron");
        $results = $qb->getQuery()->getResult();
        $brand = null;
        if (count($results) == 0) {
            $brand = new Brand();
            $brand->setName("Verney Carron");
            $brand->setPartner(1);
            $em->persist($brand);
            $em->flush();
        } else {
            foreach ($results as $result) {
                $brand = $result;
            }
        }
        if (($handle = fopen($_SERVER['DOCUMENT_ROOT'] . '/uploads/verney-carron.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $header = array("id", "created", "updated", "libelle", "type", "adresse", "adresse2", "cp", "ville", "telephone", "fax", "email", "lien", "pays", "gps");
                $init = 0;
                $array = array();
                foreach ($header as $val) {
                    if (in_array($val, array("libelle", "adresse", "adresse2", "ville"))) {
                        $json = json_decode($data[$init], true);
                        $array[$val] = utf8_decode($json["FR"]);
                    } else {
                        $array[$val] = utf8_decode($data[$init]);
                    }
                    $init++;
                }
                $adresse = $array["adresse"];
                if ($adresse != "") {
                    $adresse .= ", ";
                }
                $adresse .= $array["adresse2"];
                $qbDistrib = $manager->createQueryBuilder()->select('d')
                    ->from('AdminDistributorBundle:Distributor', 'd')
                    ->where('d.name LIKE :name')
                    ->setParameter('name', $array["libelle"]);
                $resultDistribs = $qbDistrib->getQuery()->getResult();
                if (count($resultDistribs) == 0) {
                    $distributeur = new Distributor();
                    $distributeur->setName($array["libelle"]);
                    $distributeur->setTelephone($array["telephone"]);
                    $distributeur->setEmail($array["email"]);
                    $distributeur->setAddress($adresse);
                    $distributeur->setCp(sprintf("%'.05s", $array["cp"]));
                    $distributeur->setCity($array["ville"]);
                    $distributeur->addBrand($brand);
                    $geoloc = new Geolocation();
                    if ($array["gps"] != "") {
                        $gps = explode(",", $array["gps"]);
                        $geoloc->setAddress($adresse . ", " . $array["cp"] . ", " . $array["ville"]);
                        $geoloc->setLatitude($gps[0]);
                        $geoloc->setLongitude($gps[1]);
                    } else {
                        $address = $adresse . ", " . $array["cp"] . ", " . $array["ville"]; // Google HQ
                        $prepAddr = str_replace(' ', '+', $address);
                        $geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');
                        $output = json_decode($geocode);
                        if (count($output->results)) {
                            $latitude = $output->results[0]->geometry->location->lat;
                            $longitude = $output->results[0]->geometry->location->lng;
                        } else {
                            $latitude = 0;
                            $longitude = 0;
                        }
                        $geoloc->setAddress($address);
                        $geoloc->setLatitude($latitude);
                        $geoloc->setLongitude($longitude);
                    }
                    $distributeur->setGeolocation($geoloc);

                    $em->persist($distributeur);
                    $em->flush();
                } else {
                    $distributor = $resultDistribs[0];
                    if (!$distributor->getBrands()->contains($brand)) {
                        $distributor->addBrand($brand);
                        $em->persist($distributeur);
                        $em->flush();
                    }
                }
            }
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne les distributeurs
     * EN : Returns distributors
     *
     * GET /admin/distributors?limit=10&offset=0&filter=test
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getDistributorsAction(Request $request)
    {

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', '');

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('d')
            ->from('AdminDistributorBundle:Distributor', 'd')
            ->where('d.name LIKE :name')
            ->orderBy('d.name', 'ASC')
            ->setParameter('name', '%' . strtolower($filter) . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        $distributeurs = array();

        foreach ($results as $result) {
            $distributeurs[] = $this->getFormatDistibuteurDetail($result);
        }

        return $this->view(array('distributors' => $distributeurs), Codes::HTTP_OK);
    }

    /**
     * Retourne toutes les distributeur localisées dans une zone précise
     *
     * GET /admin/distributor/map?swLat=42.16340342422401&swLng=-5.460205078125&neLat=51.02757633780243&neLng=7.965087890625&reset=1
     *
     * Coordonnées des points Nord-Est et Sud-Ouest aux extrémités de la map
     * Reset permet de réinitialiser les zones chargées
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function getDistributorMapAction(Request $request)
    {
        $this->authorize();

        $swLat = $request->query->get('swLat', false);
        $swLng = $request->query->get('swLng', false);
        $neLat = $request->query->get('neLat', false);
        $neLng = $request->query->get('neLng', false);

        if (!$swLat && !$swLng && !$neLat && !$neLng) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST);
        }

        if ($request->query->has('reset')) {
            $this->get('session')->remove('naturapass_map_distributor/positions_loaded');
        }

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();

        $qb->select(array('d'))
            ->from('AdminDistributorBundle:Distributor', 'd')
            ->orderBy('d.name', 'DESC');

        $qb->join('d.geolocation', 'g')
            ->andWhere(
                $qb->expr()->andx(
                    $qb->expr()->between('g.latitude', $swLat, $neLat), $qb->expr()->between('g.longitude', $swLng, $neLng)
                )
            );

        $alreadyLoaded = $this->get('session')->get('naturapass_map_distributor/positions_loaded');
        if (is_array($alreadyLoaded)) {
            foreach ($alreadyLoaded as $rectangle) {
                list($sw, $ne) = $rectangle;
                $qb->andWhere(
                    $qb->expr()->andx(
                        $qb->expr()->not(
                            $qb->expr()->andx(
                                $qb->expr()->between('g.latitude', $sw->getLatitude(), $ne->getLatitude()), $qb->expr()->between('g.longitude', $sw->getLongitude(), $ne->getLongitude())
                            )
                        )
                    )
                );
            }
        }

        $northEast = new Geolocation();
        $northEast->setLatitude($neLat)
            ->setLongitude($neLng);

        $southWest = new Geolocation();
        $southWest->setLatitude($swLat)
            ->setLongitude($swLng);

        $results = $qb->setMaxResults(500)
            ->getQuery()
            ->getResult();

        $alreadyLoaded[] = array($southWest, $northEast);
        $this->get('session')->set('naturapass_map_distributor/positions_loaded', $alreadyLoaded);

        $distributors = array();
        foreach ($results as $distributor) {
            $distributors[] = $this->getFormatDistibuteurDetail($distributor);
        }

        return $this->view(array('distributors' => $distributors), Codes::HTTP_OK);
    }

    /**
     * FR : Supprime un distributeur de la BDD
     * EN : Remove a distributor of database
     *
     * DELETE /admin/distributors/{distributor_id}
     *
     * @param Distributor $distributor
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("distributor", class="AdminDistributorBundle:Distributor")
     */
    public function deleteDistributorAction(Distributor $distributor)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($distributor);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

}
