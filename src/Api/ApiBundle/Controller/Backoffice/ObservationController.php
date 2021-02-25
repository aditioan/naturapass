<?php

namespace Api\ApiBundle\Controller\Backoffice;

use Admin\SentinelleBundle\Entity\Card;
use Admin\SentinelleBundle\Entity\Locality;
use Api\ApiBundle\Controller\v1\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\CardSerialization;
use Api\ApiBundle\Controller\v2\Serialization\ObservationSerialization;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\PublicationBundle\Entity\PublicationMedia;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Description of ObservationController
 *
 */
class ObservationController extends ApiRestController
{

    public function recursiveCategory($array)
    {
        $return = array();
        if (isset($array["id"]) && !in_array($array["id"], $return)) {
            $return[] = $array["id"];
            foreach ($array["children"] as $children) {
                $return = array_merge($return, $this->recursiveCategory($children));
            }
        } else if (is_array($array)) {
            foreach ($array as $node) {
                $return = array_merge($return, $this->recursiveCategory($node));
            }
        } else if (is_object($array)) {
            $array = (array)$array;
            foreach ($array as $node) {
                $return = array_merge($return, $this->recursiveCategory($node));
            }
        }
        return $return;
    }

    /**
     * FR : Retourne toutes les observations matchant le paramètre
     * EN : Returns all observations according to the parameters
     *
     * GET /backoffice/observations
     *
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupLess", "UserLess"})
     */
    public function getObservationsAction()
    {
        $this->authorize(null, 'ROLE_BACKOFFICE');
        $array = array();
        $arrayList = array();
        if ($this->get('session')->has('naturapass_backoffice/listing')) {
            $request = $this->get('session')->get('naturapass_backoffice/listing');
            $filter = array(
                "startDate" => $request->get("startDate"),
                "endDate" => $request->get("endDate"),
                "categories" => $this->recursiveCategory(json_decode($request->get("categories"), true)),
                "users" => $request->has("users") ? json_decode($request->get("users"), true) : array(),
                "groups" => $request->has("groups") ? json_decode($request->get("groups"), true) : array(),
                "localities" => $request->has("localities") ? json_decode($request->get("localities"), true) : array(),
                "insees" => $request->has("insees") ? json_decode($request->get("insees"), true) : array(),
            );
        } else {
            $filter = array(
                "startDate" => "",
                "endDate" => "",
                "categories" => array(),
                "users" => array(),
                "groups" => array(),
                "localities" => array(),
                "insees" => array(),
            );
        }
        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('obr')
            ->from('NaturaPassObservationBundle:Observation', 'obr')
            ->innerJoin('obr.publication', 'p');
        if (!empty($filter['categories'])) {
            $wheresCategory = $qb->expr()->orX();
            foreach ($filter['categories'] as $id_category) {
                $wheresCategory->add($qb->expr()->eq('obr.category', ':category' . $id_category));
                $qb->setParameter('category' . $id_category, $id_category);
            }
            $qb->andWhere($wheresCategory);
        }
        if ($filter['startDate'] != "" && $filter['endDate'] != "") {
            $qb->andWhere("obr.created >= :startDate");
            $qb->andWhere("obr.created <= :endDate");
            $qb->setParameter('startDate', $filter['startDate'] . " 00:00:00");
            $qb->setParameter('endDate', $filter['endDate'] . " 23:59:59");
        }
        if (!empty($filter['users'])) {
            $wheresUser = $qb->expr()->orX();
            foreach ($filter['users'] as $arrayUser) {
                $wheresUser->add($qb->expr()->eq('p.owner', ':owner' . $arrayUser["id"]));
                $qb->setParameter('owner' . $arrayUser["id"], $arrayUser["id"]);
            }
            $qb->andWhere($wheresUser);
        }
        if (!empty($filter['groups'])) {
            $qb->innerJoin('p.groups', 'g');
            foreach ($filter['groups'] as $arrayGroup) {
                $groupIds[] = $arrayGroup["id"];
            }
            $qb->andWhere(' g.id IN (' . join(",", $groupIds) . ')');
        }
        if (!empty($filter['localities'])) {
            $wheresLocality = $qb->expr()->orX();
            foreach ($filter['localities'] as $arrayLocality) {
                $wheresLocality->add($qb->expr()->eq('p.locality', ':locality' . $arrayLocality["id"]));
                $qb->setParameter('locality' . $arrayLocality["id"], $arrayLocality["id"]);
            }
            $qb->andWhere($wheresLocality);
        }
        if (!empty($filter['insees'])) {
            $wheresLocality = $qb->expr()->orX();
            foreach ($filter['insees'] as $arrayLocality) {
                $wheresLocality->add($qb->expr()->eq('p.locality', ':locality' . $arrayLocality["id"]));
                $qb->setParameter('locality' . $arrayLocality["id"], $arrayLocality["id"]);
            }
            $qb->andWhere($wheresLocality);
        }

        $results = $qb->setMaxResults(1000)
            ->getQuery()
            ->getResult();
        foreach ($results as $observation) {
            if ($observation->getPublication()->getGeolocation() instanceof Geolocation)
            {
                $publication = $observation->getPublication();
                if (is_null($publication->getLocality())) {
                    $locality = $this->getGeolocationService()->findACity($publication->getGeolocation());
                    if ($locality instanceof Locality) {
                        $publication->setLocality($locality);
                        $manager->persist($publication);
                    }
                    $localityName = $locality->getName();
                }else{
                    $localityName = $publication->getLocality()->getName();
                    if ($observation->getPublication()->getLocality()->getInsee())
                    {
                        $insee = $observation->getPublication()->getLocality()->getInsee();
                    }
                }
            }
            $attachments = $observation->getAttachments();

            $category = $observation->getCategory();
            if ($attachments->count() > 0) {
                $card = $attachments[0]->getLabel()->getCard();
            } else if (!is_null($category)) {
                $card = $category->getCard();
            }

            if (is_null($card)) {
                $card = new Card();
                $card->setName("pas de fiche");
            }
            $a = $card->getName();
            $a = str_replace("/", "-", $a);
            $card->setName($a);
            if (!array_key_exists($card->getName(), $array)) {
                $array[$card->getName()] = array("labels" => CardSerialization::serializeCardLabels($card->getlabels()));
                $arrayList[$card->getName()] = array();
            }

            $arrayCard = array(
                "fullname" => $observation->getPublication()->getOwner()->getFullname(),
                "email" => $observation->getPublication()->getOwner()->getEmail(),
                "latitude" => is_null($observation->getPublication()->getGeolocation()) ? "NC" : $observation->getPublication()->getGeolocation()->getLatitude(),
                "longitude" => is_null($observation->getPublication()->getGeolocation()) ? "NC" : $observation->getPublication()->getGeolocation()->getLongitude(),
                "altitude" => is_null($observation->getPublication()->getGeolocation()) ? "NC" : ((round($observation->getPublication()->getGeolocation()->getAltitude()) == 0) ? "NC" : round($observation->getPublication()->getGeolocation()->getAltitude())),
                "locality" => isset($localityName) ? $localityName : "",
                "insee" => isset($insee) ? $insee : "",
                'photo' => (!is_null($observation->getPublication()->getMedia()) && $observation->getPublication()->getMedia()->getType() == PublicationMedia::TYPE_IMAGE) ? "Oui" : "Non",
                "urlPhoto" => (!is_null($observation->getPublication()->getMedia()) && $observation->getPublication()->getMedia()->getType() == PublicationMedia::TYPE_IMAGE) ? $observation->getPublication()->getMedia()->getOriginalPath() : null,
                'video' => (!is_null($observation->getPublication()->getMedia()) && $observation->getPublication()->getMedia()->getType() == PublicationMedia::TYPE_VIDEO) ? "Oui" : "Non",
                "urlVideo" => (!is_null($observation->getPublication()->getMedia()) && $observation->getPublication()->getMedia()->getType() == PublicationMedia::TYPE_VIDEO) ? $observation->getPublication()->getMedia()->getMp4() : null,
                "content" => $observation->getPublication()->getContent(),
                "legend" => $observation->getPublication()->getLegend(),
                "date" => $observation->getCreated()->format("d-m-Y"),
                "observation" => ObservationSerialization::serializeObservation($observation)
            );


            $arrayList[$card->getName()][] = $arrayCard;
        }
        foreach ($arrayList as $cardName => $arrayDetail) {
            $array[$cardName]["list"] = $arrayDetail;
        }
        $request = $this->get('session')->set('naturapass_backoffice/array_observation', $array);
        $manager->flush();
        return $this->view($array, Codes::HTTP_OK);
    }

    /**
     * FR : crée le fichier Excel en fonction de la fiche
     * EN : make the Excel with specific card
     *
     * GET /backoffice/observations/{card}/excel
     *
     * @param String $card
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupLess", "UserLess"})
     */
    public function getObservationsExcelAction($card)
    {
        if ($this->get('session')->has('naturapass_backoffice/array_observation')) {
            $session = $this->get('session')->get('naturapass_backoffice/array_observation');
            if (isset($session[$card])) {
                $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

                $phpExcelObject->getProperties()->setCreator($this->getUser()->getFullName())
                    ->setLastModifiedBy($this->getUser()->getFullName())
                    ->setTitle("Export de la fiche " . $card)
                    ->setSubject("Export de la fiche " . $card)
                    ->setDescription("Export de la fiche " . $card);

                $aAlpha = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ'];
                $index = 1;
                $aHeader = array("A1" => "Nom", "B1" => "Email", "C1" => "Commune", "D1" => "INSEE", "E1" => "Photo", "F1" => "Video", "G1" => "Long.", "H1" => "Lat.", "I1" => "Alt.", "J1" => "Com.", "K1" => "Legende", "L1" => "Création", "M1" => "Observation");
                $indHeader = 13;
                foreach ($session[$card]["labels"] as $label) {
                    $aHeader[$aAlpha[$indHeader] . "1"] = $label["name"];
                    $indHeader++;
                }
                $index++;
                $aContent = array();
                foreach ($session[$card]["list"] as $list) {
                    $array = array(
                        $list["fullname"],
                        $list["email"],
                        $list["locality"],
                        $list["insee"],
                        $list["photo"],
                        $list["video"],
                        $list["longitude"],
                        $list["latitude"],
                        $list["altitude"],
                        $list["content"],
                        $list["legend"],
                        $list["date"],
                    );
                    $ObservationName = "";
                    foreach ($list["observation"]["tree"] as $key => $tree) {
                        if ($key > 0) {
                            $ObservationName .= "/";
                        }
                        $ObservationName .= $tree;
                    }
                    $array[] = $ObservationName;
                    foreach ($session[$card]["labels"] as $label) {
                        $found = false;
                        foreach ($list["observation"]["attachments"] as $attachment) {
                            if ($attachment["label"] == $label["name"]) {
                                if($attachment["value"]){
                                    $array[] = $attachment["value"];
                                }
                                elseif($attachment["values"]){
                                    $disVal = '';
                                    foreach ($attachment["values"] as $key => $val){
                                        $disVal = $disVal.','.$val;
                                    }
                                    $disVal = substr($disVal, 1);
                                    $array[] = $disVal;
                                }else{
                                    $array[] = null;
                                }
                                $found = true;
                            }
                        }
                        if (!$found) {
                            if ($label["type"] == 10 || $label["type"] == 11) {
                                $array[] = 0;
                            } else {
                                $array[] = "";
                            }
                        }
                    }
                    $aContent[$index] = $array;
                    $index++;
                }
                $sharedStyle1 = array('font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']], 'fill' => ['type' => 'solid', 'color' => ['rgb' => '8dbb1']], ['borders' => ['bottom' => ['style' => 'thin', 'right' => ['style' => 'medium']]]]);
                $sharedStyleBorder = array('borders' => ['bottom' => ['style' => 'thin', 'right' => ['style' => 'medium']]]);
                $activeSheet = $phpExcelObject->setActiveSheetIndex(0);
                foreach ($aHeader as $key => $value) {
                    $activeSheet->setCellValue($key, $value)->getStyle($key)->applyFromArray($sharedStyle1);
                    $phpExcelObject->getActiveSheet()->getStyle($key)->getAlignment()->setVertical('center');
                    $phpExcelObject->getActiveSheet()->getStyle($key)->getAlignment()->setHorizontal('left');
                    $phpExcelObject->getActiveSheet()->getStyle($key)->getAlignment()->setWrapText(true);
                    $phpExcelObject->getActiveSheet()->getColumnDimension(substr($key, 0, 1))->setAutoSize(true);
                }
                foreach ($aContent as $key => $array) {
                    foreach ($array as $ind => $value) {
                        $activeSheet->setCellValue($aAlpha[$ind] . $key, $value)->getStyle($aAlpha[$ind] . $key)->applyFromArray($sharedStyleBorder);
                        $phpExcelObject->getActiveSheet()->getStyle($key)->getAlignment()->setVertical('center');
                        $phpExcelObject->getActiveSheet()->getStyle($key)->getAlignment()->setHorizontal('left');
                        $phpExcelObject->getActiveSheet()->getStyle($key)->getAlignment()->setWrapText(true);
                    }
                    $index++;
                }
                $phpExcelObject->getActiveSheet()->setTitle("Fiche");
                // Set active sheet index to the first sheet, so Excel opens this as the first sheet
                $phpExcelObject->setActiveSheetIndex(0);

                // create the writer
                $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
                // create the response
                $response = $this->get('phpexcel')->createStreamedResponse($writer);
                // adding headers
                $string = date("Ymd") . '-fiche-' . $card . '.xls';
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
                    '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
                    '/[’‘‹›‚]/u' => ' ', // Literally a single quote
                    '/[“”«»„]/u' => ' ', // Double quote
                    '/ /' => '-', // nonbreaking space (equiv. to 0x160)
                );
                $nameFile = preg_replace(array_keys($utf8), array_values($utf8), $string);
                $dispositionHeader = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $nameFile
                );
                $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
                $response->headers->set('Pragma', 'public');
                $response->headers->set('Cache-Control', 'maxage=1');
                $response->headers->set('Content-Disposition', $dispositionHeader);

                return $response;
            }
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

}
