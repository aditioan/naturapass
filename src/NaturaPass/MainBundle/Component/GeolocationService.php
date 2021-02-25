<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/07/15
 * Time: 11:16
 */

namespace NaturaPass\MainBundle\Component;

use Admin\SentinelleBundle\Entity\Locality;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Util\Codes;
use NaturaPass\MainBundle\Entity\Geolocation;
use Exception;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Translation\TranslatorInterface;

class GeolocationService
{

    protected $translator;
    protected $manager;
    protected $container;
    private $allowedTypes = array(
        'locality',
        'administrative_area_level_2',
        'administrative_area_level_1',
        'country',
        'postal_code'
    );

    public static $apiKey = array('AIzaSyBpMeI-g0MtspE_N2chFRZeNfR4G0eQh7k','AIzaSyCI1px20ScIadCYY0Xz97QV0GY3GvoE48A','AIzaSyAQ4WLFtttn9HM-FEqsyfm9VfH_u6hUYoY');

    public function __construct(EntityManagerInterface $manager, TranslatorInterface $translator, Container $container)
    {
        $this->translator = $translator;
        $this->manager = $manager;
        $this->container = $container;
    }

    public function transformArray($response, $geolocation)
    {
        if ($response['status'] == 'OK') {
            $formatted = array();
            foreach ($response['results'][0]['address_components'] as $component) {
                if (count($component['types'])) {
                    foreach ($component['types'] as $type) {
                        if (in_array($type, $this->allowedTypes)) {
                            $formatted[$type] = $component['long_name'];
                        }
                    }
                }
            }
            if (!isset($formatted['locality']) && count($response['results']) >= 3) {
                for ($i = 0; $i <= 2; $i++) {
                    foreach ($response['results'][$i]['address_components'] as $component) {
                        if ($component['types'][0] == 'sublocality_level_1') {
                            $formatted['locality'] = $component['long_name'];
                            break;
                        }
                    }
                    if (isset($formatted['locality'])) {
                        break;
                    }
                }

            }
            if (!isset($formatted['administrative_area_level_2']) && count($response['results']) >= 3) {
                for ($i = 0; $i <= 2; $i++) {
                    foreach ($response['results'][$i]['address_components'] as $component) {
                        if ($component['types'][0] == 'administrative_area_level_2') {
                            $formatted['administrative_area_level_2'] = $component['long_name'];
                            break;
                        }
                    }
                    if (isset($formatted['administrative_area_level_2'])) {
                        break;
                    }
                }

            }

            //$formatted['place_id'] = $response['results'][0]['place_id'];
            return $formatted;
        } else {
//                $error = $response['error_message'];
            $error = "latitude" . $geolocation->getLatitude() . "|longitude" . $geolocation->getLongitude();
            throw new Exception($error);
        }
    }

    /**
     * Get the location of the geolocation
     *
     * @param Geolocation $geolocation
     * @return array
     *
     * @throws Exception
     */
    public function getLocationOf(Geolocation $geolocation, $aleaKey = false)
    {
        if (intval($geolocation->getLatitude()) == 0 && intval($geolocation->getLongitude()) == 0) {
            return array();
        }
        if ($aleaKey) {
            $key = GeolocationService::$apiKey[array_rand(GeolocationService::$apiKey)];
        } else {
            $key = 'AIzaSyAQ4WLFtttn9HM-FEqsyfm9VfH_u6hUYoY';
        }
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?'
            . http_build_query(
                array(
                    'key' => $key,
                    'latlng' => $geolocation->getLatitude() . ',' . $geolocation->getLongitude()
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
        $error = $this->translator->trans('errors.unknown', array(), $this->container->getParameter("translation_name") . 'api');

        if ($raw && curl_getinfo($curl, CURLINFO_HTTP_CODE) == Codes::HTTP_OK) {
            $response = json_decode($raw, true);
            return $this->transformArray($response, $geolocation);
        }

        throw new Exception($error);
    }


    /**
     * Find a city with the informations of geolocation
     *
     * @param bool $throwback Decide whether to return null or throw the exception back on error
     *
     * @return \Admin\SentinelleBundle\Entity\Locality|null
     *
     * @throws Exception
     */
    public function findACityWithResponse($location, $throwback = false)
    {
        try {
            if (isset($location['locality'])) {
                $location['name'] = $location['locality'];
                unset($location['locality']);
            }

            $locality = $this->manager->getRepository('AdminSentinelleBundle:Locality')->findOneBy($location);

            if (!$locality instanceof Locality) {
                $locality = new Locality();

                $locality->setName($location['name'])
                    ->setAdministrativeAreaLevel1(isset($location['administrative_area_level_1']) ? $location['administrative_area_level_1'] : '')
                    ->setAdministrativeAreaLevel2(isset($location['administrative_area_level_2']) ? $location['administrative_area_level_2'] : '')
                    ->setCountry($location['country'])
                    ->setPostal_code(isset($location['postal_code']) ? $location['postal_code'] : '');

                $this->manager->persist($locality);
                $this->manager->flush();
            }

            return $locality;
        } catch (Exception $exception) {
            if ($throwback) {
                throw $exception;
            }
        }

        return null;
    }

    /**
     * Find a city with the informations of geolocation
     *
     * @param Geolocation $geolocation
     * @param bool $throwback Decide whether to return null or throw the exception back on error
     *
     * @return \Admin\SentinelleBundle\Entity\Locality|null
     *
     * @throws Exception
     */
    public function findACity(Geolocation $geolocation, $throwback = false, $aleaKey = false)
    {
        try {
            $location = $this->getLocationOf($geolocation,$aleaKey);
            if (isset($location['locality'])) {
                $location['name'] = $location['locality'];
                unset($location['locality']);
            }

            $locality = $this->manager->getRepository('AdminSentinelleBundle:Locality')->findOneBy($location);

            if (!$locality instanceof Locality) {
                $locality = new Locality();

                $locality->setName($location['name'])
                    ->setAdministrativeAreaLevel1(isset($location['administrative_area_level_1']) ? $location['administrative_area_level_1'] : '')
                    ->setAdministrativeAreaLevel2(isset($location['administrative_area_level_2']) ? $location['administrative_area_level_2'] : '')
                    ->setCountry($location['country'])
                    ->setPostal_code(isset($location['postal_code']) ? $location['postal_code'] : '');

                $this->manager->persist($locality);
                $this->manager->flush();
            }

            return $locality;
        } catch (Exception $exception) {
            if ($throwback) {
                throw $exception;
            }
        }

        return null;
    }

}
