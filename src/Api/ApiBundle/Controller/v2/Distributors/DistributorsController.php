<?php

namespace Api\ApiBundle\Controller\v2\Distributors;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\DistributorSerialization;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use NaturaPass\MainBundle\Entity\Geolocation;
use Admin\DistributorBundle\Entity\Distributor;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DistributorsController extends ApiRestController
{

    public function putGeolocationAddressesUpdateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $limit = $request->query->get('limit',100);
        $longmin = -6;
        $longmax = 10;
        $latmin = 41;
        $latmax = 55;
        $newAddresses = array();
        $geolocations_repository = $em->getRepository(geolocation::class)->createQueryBuilder('g');

        $geolocations = $geolocations_repository->where('g.latitude != 0')
        ->andWhere('g.longitude != 0')
        ->andWhere('g.latitude IS NOT NULL')
        ->andWhere('g.longitude IS NOT NULL')
        ->andWhere('g.longitude between :longmin and :longmax')
        ->andWhere('g.latitude between :latmin and :latmax')
        ->andWhere('g.addressUpdated = 0')
        ->setParameter('latmin',$latmin)
        ->setParameter('latmax',$latmax)
        ->setParameter('longmin',$longmin)
        ->setParameter('longmax',$longmax)
        ->setMaxResults($limit)
        ->getQuery()->getResult();

        foreach ($geolocations as $geolocation) { // grab geolocation addresses from google
            $newAddress = $this->getGeolocationAddress($geolocation->getLatitude().','.$geolocation->getLongitude(),"locality");
            if ($newAddress) {
                array_push($newAddresses, $newAddress);
            }
            else{
                array_push($newAddresses, $geolocations->getAddress());
            }
        }

        foreach ($geolocations as $key => $geolocation) { //save obtained addresses into database

            try{
                $geolocation->setAddress($newAddresses[$key]);
                $geolocation->setAddressUpdated(1);
                $em->persist($geolocation);
                $em->flush();
            }
            catch(DBALException $e){
                return $this->view($e->getMessage(), Codes::HTTP_BAD_REQUEST);
            }
            
        }

        return $this->view("Addresses Updated!", Codes::HTTP_OK);
    }

    /**
     * get Sqlite of all distributors in map
     *
     * GET /v2/distributors/sqlite?updated=1450860029
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getDistributorsSqliteAction(Request $request)
    {
        $this->authorize();
        $updated = $request->query->get('updated', false);
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('d')
            ->from('AdminDistributorBundle:Distributor', 'd')
//            ->setFirstResult($offset)
//            ->setMaxResults($limit)
            ->getQuery();

        $paginators = new Paginator($qb, $fetchJoinCollection = true);
        $return = array("sqlite" => array());
        foreach ($paginators as $distributor) {
            $get = DistributorSerialization::serializeDistributorSqliteRefresh($updated, $distributor);
            if (!is_null($get)) {
                $return["sqlite"][] = $get;
            }
        }
        return $this->view($return, Codes::HTTP_OK);
    }

    /**
     * get distributor data
     *
     * GET /v2/distributor?id=1204
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getDistributorAction(Request $request)
    {
        $distributor_id = $request->query->get('id');
        $manager = $this->getDoctrine()->getManager();
        $distributor = $manager->createQueryBuilder()->select('d.id','d.name','d.address','d.city','d.cp','d.telephone')
            ->from('AdminDistributorBundle:Distributor', 'd')
            ->where('d.id = :distributor_id')
            ->setParameter('distributor_id',$distributor_id)
            ->getQuery()->getResult();

        // var_dump($distributor);
        if (!$distributor) {
            return $this->view(array(), Codes::HTTP_OK);
        }

        foreach ($distributor as $row) {
            $arr[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'address' => $row['address'],
                'cp' => $row['cp'],
                'telephone' => $row['telephone'],
                'city' => $row['city']
            );
        }
        return $this->view($arr, Codes::HTTP_OK);
    }

    /**
     * get nearest distributors
     *
     * GET /v2/distributors/nearest?city=Paris
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getDistributorsNearestAction(Request $request)
    {
        $max_result=3;
        $search_radius=150;//(in KM)
        $bearings = array(0,90,180,270);
        $search_bounds = array();

        /*Get City geolocation*/
        $city = $request->query->get('city');
        $city = str_replace (" ", "+", $city);
        $city_geolocation = $this->getCityGeolocation($city);
        /*-----------------------*/

        if (is_null($city_geolocation['lat']) || is_null($city_geolocation['lng'])) {
            return $this->view(array(), Codes::HTTP_OK);
        }

        $manager = $this->getDoctrine()->getManager();
        $distributors = $manager->createQueryBuilder()
        ->select(array('d.id','d.name','d.address','d.city','d.cp','d.telephone','(3959*acos(cos(radians(:city_lat))*cos(radians(g.latitude))*cos(radians(g.longitude)-radians(:city_lng))+sin(radians(:city_lat))*sin(radians(g.latitude)))) AS distance'))
        ->from('AdminDistributorBundle:Distributor','d')
        ->from('NaturaPassMainBundle:Geolocation','g')
        ->where('d.geolocation = g.id')
        ->setParameter('city_lat', $city_geolocation['lat'])
        ->setParameter('city_lng', $city_geolocation['lng'])
        ->orderBy('distance')
        ->setMaxResults($max_result)
        ->getQuery()->getResult();
        // var_dump($distributors);
        foreach ($distributors as $row) {
            $arr[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'address' => $row['address'],
                'cp' => $row['cp'],
                'telephone' => $row['telephone'],
                'city' => $row['city'],
                'dist' => $row['distance']
            );
        }
        return $this->view(array('list' => $arr), Codes::HTTP_OK);
    }

    public function getCityGeolocation($city,$api_key = null)
    {
        if(!$api_key){
            $api_key = $this->getParameter('google_api_key');
        }
        $details_url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$city."&components=country:FR|adinistrative_area:administrative_area_2&key=".$api_key;
        $return = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $details_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $geoloc = json_decode(curl_exec($ch), true);

        $return['lat']=$geoloc["results"][0]["geometry"]["location"]["lat"];
        $return['lng']=$geoloc["results"][0]["geometry"]["location"]["lng"];

        return $return;
    }

    public function getGeolocationAddress($latlng = "0,0",$result_type = "locality")
    {
        $api_key = $this->getParameter('google_api_key');
        $details_url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$latlng."&result_type=".$result_type."&key=".$api_key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $details_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $address = json_decode(curl_exec($ch), true);

        $return=$address["results"][0]['address_components'][0]['long_name'];

        return $return;
    }

}
