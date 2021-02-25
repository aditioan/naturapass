<?php

namespace Api\ApiBundle\Controller\v2\Distributors;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Distributors\DistributorsController;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Util\Codes;
use Doctrine\ORM\QueryBuilder;
use Admin\DistributorBundle\Entity\DistributorEvent;
use Admin\DistributorBundle\Entity\Distributor;
use Admin\DistributorBundle\Entity\DistributorEventSubscriber;
use Symfony\Component\Validator\Constraints\DateTime;
use Doctrine\DBAL\DBALException;


class DistributorEventsController extends ApiRestController{
	
	/**
     * Add a distributor event
     *
     * POST /v2/distributor/event
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
	public function postDistributorEventAction(Request $request){
		$this->authorize();
		$params = $request->request->get('DistributorEvent');
		$doctrine=$this->getDoctrine();
		$em = $doctrine->getManager();
		$distributor=$doctrine->getRepository('AdminDistributorBundle:Distributor')->findOneById($params['distributor']);
		
		if (!$distributor) {
        	return $this->view("Distributor not found", Codes::HTTP_FAILED_DEPENDENCY);
    	}

        // var_dump($params['startDate'],$params['endDate']);
		$DistributorEvent = new DistributorEvent();
		$DistributorEvent->setDistributor($distributor);

		$DistributorEvent->setStartDate($params['startDate'] ? new \DateTime($params['startDate']) : null);
		$DistributorEvent->setEndDate($params['endDate'] ? new \DateTime($params['endDate']) : null);
		$DistributorEvent->setDescription($params['description']);
        $DistributorEvent->setName($params['name']);
        $DistributorEvent->setPlaceName($params['placeName']);
        $DistributorEvent->setPlaceAddress($params['placeAddress']);
        $DistributorEvent->setHours($params['hours']);

		$em->persist($DistributorEvent);

		$em->flush();

		return $this->view($this->success(), Codes::HTTP_CREATED);

    }

    /**
     * Add a subscriber for distributor event
     *
     * POST /v2/distributor/event/subscriber
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function postDistributorEventSubscriberAction(Request $request){
        $params = $request->request->get('DistributorEventSubscriber');
        $doctrine=$this->getDoctrine();
        $em = $doctrine->getManager();

        $event=$doctrine->getRepository('AdminDistributorBundle:DistributorEvent')->find($params['event']);
        if (!$event) {
            return $this->view("Event not found", Codes::HTTP_FAILED_DEPENDENCY);
        }
        $distributor = $doctrine->getRepository('AdminDistributorBundle:Distributor')->findOneById($event->getDistributor());
        $distributor_email=$distributor->getEmail();
        

        

        $emailRegisteredToEvent = $em->createQueryBuilder()
        ->select('s')
        ->from('AdminDistributorBundle:DistributorEventSubscriber','s')
        ->innerJoin('s.event','e')
        ->where('s.email = :email')
        ->andWhere('e.id = :event')
        ->setParameter('email',$params['email'])
        ->setParameter('event',$params['event'])
        ->getQuery()->getResult();

        if ($emailRegisteredToEvent) {
            return $this->view("Email already registered", Codes::HTTP_BAD_REQUEST);
        }

        $EventSubscriber = new DistributorEventSubscriber();
        $EventSubscriber->setLastName($params['lastName']);
        $EventSubscriber->setFirstName($params['firstName']);
        $EventSubscriber->setEmail($params['email']);
        $EventSubscriber->setPhoneNumber($params['phoneNumber']);
        $EventSubscriber->setBirthDate(new \DateTime($params['birthDate']));
        $EventSubscriber->setEvent($event);
        $EventSubscriber->setZipCode($params['zipCode']);

        

        try{
            $em->persist($EventSubscriber);
            $em->flush();
        }
        catch(DBALException $e){
            return $this->view($e->getMessage(), Codes::HTTP_BAD_REQUEST);
        }

        if ($distributor_email!="") {
            $this->sendMailToDistributor($params,$distributor_email);
        }
        if ($params['event'] != '10') {
            $this->sendMailToSubscriber($params['email'], $distributor, $event->getStartDate());
            return $this->view($this->success(), Codes::HTTP_CREATED);

        }
    }

    private function sendMailToDistributor($params,$distributor_email)
    {
        $from="team@naturapass.com";
        $message = \Swift_Message::newInstance()
        ->setContentType("text/html")
        ->setSubject($this->container->get('translator')->trans('rivolier.subscriber_registration.to_armory.subject', array(), $this->container->getParameter("translation_name") . 'email'))
        ->setFrom($from)
        ->setCc(["m.laurent@heolys.fr", "p.burdeyron@heolys.fr", "v.amagat@heolys.fr"])
        ->setTo($distributor_email)
        ->setBody(
            $this->container->get('templating')->render(
                'AdminDistributorBundle:Rivolier:subscriber_registration_armory.html.twig',
                array(
                    'lastName' => $params['lastName'],
                    'firstName' => $params['firstName'],
                    'email' => $params['email'],
                    'birthDate' => $params['birthDate'],
                    'phoneNumber' => $params['phoneNumber'],
                    'zipCode' => $params['zipCode']
                )
            )
        );

        $this->container->get('mailer')->send($message);
    }

    private function sendMailToSubscriber($subscriber_email, $distributor, $eventDate)
    {
        $from="team@naturapass.com";
        $message = \Swift_Message::newInstance()
        ->setContentType("text/html")
        ->setSubject($this->container->get('translator')->trans('rivolier.subscriber_registration.to_subscriber.subject', array(), $this->container->getParameter("translation_name") . 'email'))
        ->setFrom($from)
        ->setTo($subscriber_email)
        ->setBody(
            $this->container->get('templating')->render(
                'AdminDistributorBundle:Rivolier:subscriber_registration_subscriber.html.twig',
                array(
                    'eventDate' => $eventDate,
                    'armory' => $distributor->getName(),
                    'address' => $distributor->getAddress(),
                    'cp' => $distributor->getCp(),
                    'city' => $distributor->getCity(),
                    'phone' => $distributor->getTelephone(),
                )
            )
        );

        $this->container->get('mailer')->send($message);
    }

    /**
     * get distributors event details
     *
     * GET /v2/distributor/event?id=0&city=Paris
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getDistributorEventAction(Request $request)
    {
        /*Get City geolocation*/
        $city = $request->query->get('city','Paris');
        $city = str_replace (" ", "+", $city);
        $DistributorsController = new DistributorsController();
        $google_api_key = $this->getParameter('google_api_key');
        $city_geolocation = $DistributorsController->getCityGeolocation($city,$google_api_key);
        /*-----------------------*/

        $eventId = $params = $request->query->get('id',0);
        $manager = $this->getDoctrine()->getManager();
        $eventDetails = $manager->createQueryBuilder()
            ->select(array('de.id','d.id as distributor_id','d.name','d.address','d.city','d.cp','d.telephone','de.startDate','de.placeName','de.placeAddress','de.hours','de.description','de.endDate','d.email','(3959*acos(cos(radians(:city_lat))*cos(radians(g.latitude))*cos(radians(g.longitude)-radians(:city_lng))+sin(radians(:city_lat))*sin(radians(g.latitude)))) AS distance'))
        ->from('AdminDistributorBundle:DistributorEvent','de')
        ->innerJoin('de.distributor','d')
        ->innerJoin('d.geolocation','g')
        ->where('de.id = :eventId')
        ->setParameter('city_lat', $city_geolocation['lat'])
        ->setParameter('city_lng', $city_geolocation['lng'])
        ->setParameter('eventId',$eventId)
        ->getQuery()->getResult();
        foreach ($eventDetails as $row) {
            $arr[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'distributor_id' => $row['distributor_id'],
                'address' => rtrim($row['address'], ', '),
                'cp' => $row['cp'],
                'telephone' => $row['telephone'],
                'city' => $row['city'],
                'dist' => $row['distance'],
                'startDate' => $row['startDate'],
                'placeName' => $row['placeName'],
                'placeAddress' => $row['placeAddress'],
                'description' => $row['description'],
                'hours' => $row['hours'],
                'endDate' => $row['endDate'],
                'email' => $row['email']
            );
        }
        return $this->view($arr[0], Codes::HTTP_OK);
        // $distributorEvent=$doctrine->getRepository(DistributorEvent::class)->find($params['distributor']);
    }

    /**
     * get nearest distributors events
     *
     * GET /v2/distributors/events/nearest?city=Paris
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getDistributorsEventsNearestAction(Request $request)
    {
        $max_result=3;
        $search_radius=150;//(in KM)
        $bearings = array(0,90,180,270);
        $search_bounds = array();

        /*Get City geolocation*/
        $city = $request->query->get('city');
        $city = str_replace (" ", "+", $city);
        $DistributorsController = new DistributorsController();
        $google_api_key = $this->getParameter('google_api_key');
        $city_geolocation = $DistributorsController->getCityGeolocation($city,$google_api_key);
        /*-----------------------*/

        if (is_null($city_geolocation['lat']) || is_null($city_geolocation['lng'])) {
            return $this->view(array(), Codes::HTTP_OK);
        }

        $manager = $this->getDoctrine()->getManager();
        $distributors = $manager->createQueryBuilder()
        ->select(array('de.id','d.id as distributor_id','d.name','d.address','d.city','d.cp','d.telephone','de.placeName','de.placeAddress','de.hours','de.description','de.startDate','de.endDate','d.email','(3959*acos(cos(radians(:city_lat))*cos(radians(g.latitude))*cos(radians(g.longitude)-radians(:city_lng))+sin(radians(:city_lat))*sin(radians(g.latitude)))) AS distance'))
        ->from('AdminDistributorBundle:DistributorEvent','de')
        ->innerJoin('de.distributor','d')
        ->innerJoin('d.geolocation','g')
        ->where('(3959*acos(cos(radians(:city_lat))*cos(radians(g.latitude))*cos(radians(g.longitude)-radians(:city_lng))+sin(radians(:city_lat))*sin(radians(g.latitude)))) <= :search_radius')
        ->setParameter('city_lat', $city_geolocation['lat'])
        ->setParameter('city_lng', $city_geolocation['lng'])
        ->setParameter('search_radius',$search_radius)
        ->orderBy('distance')
        ->setMaxResults($max_result)
        ->getQuery()->getResult();
        // var_dump($distributors);
        $arr = array();
        foreach ($distributors as $row) {
            $arr[] = array(
                'id' => $row['id'],
                'distributor_id' => $row['distributor_id'],
                'name' => $row['name'],
                'address' => rtrim($row['address'], ', '),
                'cp' => $row['cp'],
                'telephone' => $row['telephone'],
                'city' => $row['city'],
                'dist' => $row['distance'],
                'startDate' => $row['startDate'],
                'endDate' => $row['endDate'],
                'placeName' => $row['placeName'],
                'placeAddress' => $row['placeAddress'],
                'description' => $row['description'],
                'hours' => $row['hours'],
                'email' => $row['email']
            );
        }

        return $this->view(array('DistributorEvents' => $arr), Codes::HTTP_OK);
    }
}
