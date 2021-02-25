<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 15/06/14
 * Time: 16:09
 */

namespace Api\ApiBundle\Controller\v1;


use Admin\SentinelleBundle\Entity\Category;
use Admin\SentinelleBundle\Entity\Receiver;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\LoungeBundle\Entity\Lounge;
use NaturaPass\MainBundle\Entity\Geolocation;
use NaturaPass\MainBundle\Entity\Shape;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\ObservationBundle\Entity\AttachmentReceiver;
use NaturaPass\ObservationBundle\Entity\Observation;
use NaturaPass\ObservationBundle\Entity\ObservationReceiver;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationDeleted;
use NaturaPass\UserBundle\Entity\PaperModel;
use NaturaPass\UserBundle\Entity\PaperParameter;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class CronController
 * @package Api\ApiBundle\Controller\v1
 *
 * Gère les tâches à effectuer périodiquement
 *
 * Doit être utilisé avec la clé correspondante
 */
class CronController extends ApiRestController
{

    const KEY = 'qzCSWMJBheJXVLTsz3M3dSqvFo0WWQJpKWpJiTfjnGuhg0dAJdzHsYvYjYkt4Qi';

    /**
     * Autorise une requête de tâche périodique
     *
     * @param Request $request
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function allow(Request $request)
    {
        if ($request->query->get('key', false) != self::KEY) {
            throw new HttpException(Codes::HTTP_FORBIDDEN, $this->message('codes.403'));
        }
    }

    /**
     * met en place l'interfaçage EPOS
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronUserPaperAction(Request $request)
    {
        $this->allow($request);
        $manager = $this->getDoctrine()->getManager();
        $models = $manager->getRepository('NaturaPassUserBundle:PaperModel')->findAll();
        $users = $manager->getRepository('NaturaPassUserBundle:User')->findAll();
//        $users = array($manager->getRepository('NaturaPassUserBundle:User')->find(3));
        foreach ($users as $user) {
            foreach ($models as $model) {
                $paperExist = $manager->getRepository('NaturaPassUserBundle:PaperParameter')->findOneBy(array("owner" => $user, "name" => $model->getName()));
                if (is_null($paperExist)) {
                    $paperExist = new PaperParameter();
                    $paperExist->setOwner($user);
                    $paperExist->setType($model->getType());
                    $paperExist->setDeletable(PaperParameter::NO_DELETABLE);
                    $paperExist->setName($model->getName());
                    $manager->persist($paperExist);
                    $manager->flush();
                }
            }
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * met en place l'interfaçage EPOS
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronAddGameFairSharingAction(Request $request)
    {
        $this->allow($request);
        $manager = $this->getDoctrine()->getManager();
        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(267);
        $lounge = $manager->getRepository('NaturaPassLoungeBundle:Lounge')->find(516);

//        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(84);
//        $lounge = $manager->getRepository('NaturaPassLoungeBundle:Lounge')->find(378);

        foreach ($group->getPublications() as $publication) {
            $publication->getSharing()->setShare(Sharing::NATURAPASS);
            if (!$publication->hasHunt($lounge) instanceof Lounge) {
                $publication->addHunt($lounge);
            }
            $manager->persist($publication);
            $manager->flush();
        }
        foreach ($group->getShapes() as $shape) {
            $shape->getSharing()->setShare(Sharing::NATURAPASS);
            if (!$shape->hasHunt($lounge) instanceof Lounge) {
                $shape->addHunt($lounge);
            }
            $manager->persist($shape);
            $manager->flush();
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * met en place l'interfaçage EPOS
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronAddGameFairSharingwithoutstandAction(Request $request)
    {
        $this->allow($request);
        $manager = $this->getDoctrine()->getManager();
        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(267);
        $lounge = $manager->getRepository('NaturaPassLoungeBundle:Lounge')->find(516);

//        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find(84);
//        $lounge = $manager->getRepository('NaturaPassLoungeBundle:Lounge')->find(378);

        foreach ($group->getPublications() as $publication) {
            $include = true;
            foreach ($publication->getObservations() as $observation) {
                if ($observation->getCategory()->getId() == 689) {
                    $include = false;
                }
            }

            if ($include && !$publication->hasHunt($lounge) instanceof Lounge) {
                $publication->addHunt($lounge);
                $publication->getSharing()->setShare(Sharing::NATURAPASS);
                $manager->persist($publication);
                $manager->flush();
            }
        }
        foreach ($group->getShapes() as $shape) {
            if (!$shape->hasHunt($lounge) instanceof Lounge) {
                $shape->addHunt($lounge);
            }
            $shape->getSharing()->setShare(Sharing::NATURAPASS);
            $manager->persist($shape);
            $manager->flush();
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * met en place l'interfaçage EPOS
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronChangeAnthoUserAction(Request $request)
    {
        $this->allow($request);
        $manager = $this->getDoctrine()->getManager();
        $publications = $manager->createQueryBuilder()
            ->select('p')
            ->from('NaturaPassPublicationBundle:Publication', 'p')
            ->where('p.owner = :owner AND p.created >= :date')
            ->setParameter('owner', 15)
//            ->setParameter('owner', 1893)
            ->setParameter('date', "2016-03-29")
            ->orderBy('p.created', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($publications as $publication) {
//            $publication = new Publication();
            $publication->setOwner($manager->getRepository('NaturaPassUserBundle:User')->find(5969));
//            $publication->setOwner($manager->getRepository('NaturaPassUserBundle:User')->find(3));
            $manager->persist($publication);
            $manager->flush();
        }

        $shapes = $manager->createQueryBuilder()
            ->select('s')
            ->from('NaturaPassMainBundle:Shape', 's')
            ->where('s.owner = :owner AND s.created >= :date')
            ->setParameter('owner', 15)
//            ->setParameter('owner', 1893)
            ->setParameter('date', "2016-03-29")
            ->orderBy('s.created', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($shapes as $shape) {
//            $shape = new Shape();
            $shape->setOwner($manager->getRepository('NaturaPassUserBundle:User')->find(5969));
//            $shape->setOwner($manager->getRepository('NaturaPassUserBundle:User')->find(3));
            $manager->persist($shape);
            $manager->flush();
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * ajoute le partage des FDC à l'ensemble des publications du groupe
     *
     * GET /api/v1/cron/fdc/backoffice?key=qzCSWMJBheJXVLTsz3M3dSqvFo0WWQJpKWpJiTfjnGuhg0dAJdzHsYvYjYkt4Qi&group=1&receiver=1
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronFdcBackofficeAction(Request $request)
    {
        $this->allow($request);
        $manager = $this->getDoctrine()->getManager();
        $receiver = $manager->getRepository('AdminSentinelleBundle:Receiver')->find($request->query->get('receiver', null));
        $group = $manager->getRepository('NaturaPassGroupBundle:Group')->find($request->query->get('group', null));
        if (!is_null($receiver) && !is_null($group)) {
//            $group = new Group();
            foreach ($group->getPublications() as $publication) {
                foreach ($publication->getObservations() as $observation) {
                    if (!$observation->getReceivers()->contains($receiver)) {
                        $observation->addReceiver($receiver);
                        $manager->persist($observation);
                        $manager->flush();
                        $observationReceiver = new ObservationReceiver();
                        $observationReceiver->duplicateObservation($observation);
                        $observationReceiver->setReceiver($receiver);
                        $manager->persist($observationReceiver);
                        $manager->flush();
                        foreach ($observation->getAttachments() as $attachment) {
                            $attachmentreceiver = new AttachmentReceiver();
                            $attachmentreceiver->duplicateAttachment($attachment);
                            $attachmentreceiver->setObservationreceiver($observationReceiver);
                            $manager->persist($attachmentreceiver);
                            $manager->flush();
                        }
                    }
                }
            }
            return $this->view($this->success(), Codes::HTTP_OK);
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * met en place l'interfaçage EPOS
     *
     * GET /api/v1/cron/epos/damage
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronEposDamageAction(Request $request)
    {
//        $this->allow($request);

        $manager = $this->getDoctrine()->getManager();

        $receivers = $manager->getRepository('AdminSentinelleBundle:Receiver')->findAll();
        foreach ($receivers as $receiver) {
            $ftp_server = "62.210.29.23";
            $ftp_user = $receiver->getFtplogin();
            $ftp_pass = $receiver->getFtppassword();
//            $ftp_server = "192.168.200.130";
//            $ftp_user = "naturapass.e-conception.fr";
//            $ftp_pass = "nicolas";
            // Mise en place d'une connexion basique
            $conn_id = ftp_connect($ftp_server) or die("Couldn't connect to $ftp_server");
            // Tentative d'identification
            if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
                $array = array();
                $array[] = array(
                    'id' => 'ID',
                    'date' => 'Date',
                    'id_category' => 'Category ID',
                    'name_category' => 'Category Name',
                    'id_locality' => 'Locality ID',
                    'code_insee' => 'Insee',
                    'name_locality' => 'Locality Name',
                    'id_animal' => 'Animal ID',
                    'name_animal' => 'Animal Name',
                    'damage_amount' => 'Damage Amount',
                    'quantity_destroyed' => 'Quantity Destroyed',
                    'comment' => 'Comment',
                    'id_user' => 'UserID',
                    'name_user' => 'User Name',
                    'email_user' => 'User Email',
                );
                $observations = $receiver->getObservations();
                foreach ($observations as $observationReceiver) {
                    $animal = $observationReceiver->getAnimal();
                    $category = $observationReceiver->getCategory();
                    if (is_null($animal) && !is_null($category)) {
                        $animal = $category->getAnimalTree($manager);
                    }
                    if (is_object($category) && $category->isCategoryToEpos()) {
                        $attachementsReceiver = $observationReceiver->getAttachmentreceivers();
                        if ($attachementsReceiver->count() > 0) {
                            $card = $attachementsReceiver[0]->getLabel()->getCard();
                        } else {
                            $zone = null;
                            foreach ($receiver->getLocalities() as $locality) {
                                if (!is_null($zone)) {
                                    $zone = (is_object($locality) && is_object($locality->getZone())) ? $locality->getZone() : null;
                                    $card = (!is_null($category)) ? $category->getCardszone($zone) : null;
                                }
                            }
                        }
                        if (is_object($card) && $card->isCardToEpos()) {
                            $damage = 0;
                            $quantity = 0;
                            foreach ($attachementsReceiver as $attachementReceiver) {
                                if (in_array($attachementReceiver->getLabel()->getName(), array("Surface"))) {
                                    $quantity = $attachementReceiver->getValue();
                                }
                                if (in_array($attachementReceiver->getLabel()->getName(), array("Montant du préjudice estimé"))) {
                                    $damage = $attachementReceiver->getValue();
                                }
                            }
                            $array[] = array(
                                'id' => $observationReceiver->getId(),
                                'date' => $observationReceiver->getCreated()->format("Y-m-d H:i:s"),
                                'id_category' => $category->getId(),
                                'name_category' => $category->getName(),
                                'id_locality' => $observationReceiver->getLocality()->getId(),
                                'code_insee' => $observationReceiver->getLocality()->getInsee(),
                                'name_locality' => $observationReceiver->getLocality()->getName(),
                                'id_animal' => (!is_null($animal)) ? $animal->getId() : "",
                                'name_animal' => (!is_null($animal)) ? $animal->getName_fr() : "",
                                'damage_amount' => $damage,
                                'quantity_destroyed' => $quantity,
                                'comment' => $observationReceiver->getContent(),
                                'id_user' => $observationReceiver->getUser()->getId(),
                                'name_user' => $observationReceiver->getFullName(),
                                'email_user' => $observationReceiver->getEmail(),
                            );
                        }
                    }
                }

                $groups = $receiver->getGroups();
                foreach ($groups as $group) {
                    $publications = $group->getPublications();
                    foreach ($publications as $publication){
                        if(count($publication->getObservations())>0){
                            $animal = $publication->getObservations()[0]->getAnimal();
                            $category = $publication->getObservations()[0]->getCategory();
                            if (is_null($animal) && !is_null($category)) {
                                $animal = $category->getAnimalTree($manager);
                            }
                            if (is_object($category) && $category->isCategoryToEpos()) {
                                $attachementsReceiver = $publication->getObservations()[0]->getAttachments();
                                if ($attachementsReceiver->count() > 0) {
                                    $card = $attachementsReceiver[0]->getLabel()->getCard();
                                } else {
                                    $zone = null;
                                    $locality = $publication->getLocality();
                                    if (!is_null($zone)) {
                                        $zone = (is_object($locality) && is_object($locality->getZone())) ? $locality->getZone() : null;
                                        $card = (!is_null($category)) ? $category->getCardszone($zone) : null;
                                    }
                                }
                                if (is_object($card) && $card->isCardToEpos()) {
                                    $damage = 0;
                                    $quantity = 0;
                                    foreach ($attachementsReceiver as $attachementReceiver) {
                                        if (in_array($attachementReceiver->getLabel()->getName(), array("Surface"))) {
                                            $quantity = $attachementReceiver->getValue();
                                        }
                                        if (in_array($attachementReceiver->getLabel()->getName(), array("Montant du préjudice estimé"))) {
                                            $damage = $attachementReceiver->getValue();
                                        }
                                    }
                                    $array[] = array(
                                        'id' => $publication->getId(),
                                        'date' => $publication->getCreated()->format("Y-m-d H:i:s"),
                                        'id_category' => $category->getId(),
                                        'name_category' => $category->getName(),
                                        'id_locality' => $publication->getLocality()->getId(),
                                        'code_insee' => $publication->getLocality()->getInsee(),
                                        'name_locality' => $publication->getLocality()->getName(),
                                        'id_animal' => (!is_null($animal)) ? $animal->getId() : "",
                                        'name_animal' => (!is_null($animal)) ? $animal->getName_fr() : "",
                                        'damage_amount' => $damage,
                                        'quantity_destroyed' => $quantity,
                                        'comment' => $publication->getContent(),
                                        'id_user' => $publication->getOwner()->getId(),
                                        'name_user' => $publication->getOwner()->getFullName(),
                                        'email_user' => $publication->getOwner()->getEmail(),
                                    );
                                }
                            }
                        }
                    }

                }

                $fp = tmpfile();
                $path = array_search('uri', @array_flip(stream_get_meta_data($fp)));
                foreach ($array as $fields) {
                    $fields = array_map("utf8_decode", $fields);
                    fputcsv($fp, $fields, ";");
                }
                ftp_pasv($conn_id, false);
                ftp_put($conn_id, 'files/'.$receiver->getName() . '_dommages.csv', $path, FTP_ASCII);
                fclose($fp);
            } else {
                echo "Connexion impossible en tant que $ftp_user\n";
            }

            // Fermeture de la connexion
            ftp_close($conn_id);
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Créé les recommandations d'amis
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getCronGraphRecommendationsAction(Request $request)
    {
        $this->allow($request);

        $users = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:User')->findBy(array('enabled' => true));

        foreach ($users as $user) {
            $results = $this->getGraphService()->generateUserRecommendations($user)->slice(0, 5);

            $recommendations = new ArrayCollection();
            foreach ($results as $recommendation) {
                $recommendations->add($this->getFormatUser($recommendation->getTarget(), true));
            }

            $this->getEmailService()->generate(
                'graph.recommendation',
                array(),
                array($user),
                'NaturaPassEmailBundle:Graph:recommendation.html.twig',
                array(
                    'fullname' => $user->getFullname(),
                    'recommendations' => $recommendations
                )
            );
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Supprime les publication en double
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public function getCronDeleteDuplicatedPublicationAction(Request $request)
    {
        $this->allow($request);

        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('NaturaPassPublicationBundle:Publication')->createQueryBuilder('p');
        $goodIds = $qb->select('p.id')
            ->groupBy('p.owner,p.content,p.created,p.legend')
            ->getQuery()
            ->getResult();

        $qb2 = $this->getDoctrine()->getManager()->getRepository('NaturaPassPublicationBundle:Publication')->createQueryBuilder('p');
        $publicationsToDelete = $qb2->select('p')
            ->Where("p.id NOT IN (:ids)")
            ->setParameter('ids', $goodIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult();
        foreach ($publicationsToDelete as $publication) {
            $id = $publication->getId();
            $publicationDeleted = new PublicationDeleted();
            $publicationDeleted->setId($id);
            if (!is_null($publication->getGeolocation())) {
                $geolocation = new Geolocation();
                $geolocation->setAddress($publication->getGeolocation()->getAddress());
                $geolocation->setAltitude($publication->getGeolocation()->getAltitude());
                $geolocation->setLatitude($publication->getGeolocation()->getLatitude());
                $geolocation->setLongitude($publication->getGeolocation()->getLongitude());
                $publicationDeleted->setGeolocation($geolocation);
            }

            $em->persist($publicationDeleted);
            $em->remove($publication);
            $em->flush();
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Envois des résumés de groupes aux utilisateurs qui ont choisis d'être avertis
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronGroupSummaryAction(Request $request)
    {
        $this->allow($request);

        $qb = $this->getDoctrine()->getManager()->getRepository('NaturaPassGroupBundle:Group')->createQueryBuilder('g');

        $created = new \DateTime();
        $created->sub(new \DateInterval('P1D'));

        /**
         * @var QueryBuilder $qb
         */
        $groups = $qb->select('g')
            ->leftJoin('g.publications', 'p')
            ->where('p.created > :created')
            ->setParameter('created', $created)
            ->getQuery()
            ->getResult();

        $result = array();

        foreach ($groups as $group) {
            $result[] = $group->getName();

            $publications = $group->getPublications();
            $senders = array();

            foreach ($publications as $publication) {
                if ($publication->getCreated() >= $created && in_array($publication->getOwner()->getFullname(), $senders)) {
                    $senders[] = $publication->getOwner()->getFullname();
                }
            }
            if (count($senders)) {
                $senders = array_unique($senders);

                $this->getEmailService()->generate(
                    'group.publication_added',
                    array('%group%' => $group->getName()),
                    $group->getEmailableSubscribers()->toArray(),
                    'NaturaPassEmailBundle:Group:publication-added.html.twig',
                    array('group' => $group, 'senders' => $this->getNotificationService()->getLinkedValues(new ArrayCollection($senders)))
                );
            }
        }

        return $this->view($result, count($result) ? Codes::HTTP_CREATED : Codes::HTTP_OK);
    }

    /**
     * Envoi les notifications de résumé de salons
     *
     * PUT /crons/lounges/summaries
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     */
    public function getCronLoungeSummaryAction(Request $request)
    {
        $this->allow($request);

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();

        $lounges = $qb->select('l')
            ->from('NaturaPassLoungeBundle:Lounge', 'l')
            ->join('l.messages', 'lm')
            ->where('DATE_DIFF(lm.created, :date) = 0')
            ->setParameter(':date', date('Y-m-d'))
            ->getQuery()
            ->getResult();

        foreach ($lounges as $lounge) {
            $users = array();

            foreach ($lounge->getSubscribers() as $subscriber) {
                $users[] = $subscriber->getUser();
            }

            $this->getEmailService()->generate(
                'lounge.chat',
                array('%loungename%' => $lounge->getName()),
                $users,
                'NaturaPassEmailBundle:Lounge:chat-email.html.twig',
                array('lounge' => $lounge)
            );
        }

        return $this->view($this->success(), Codes::HTTP_OK);
    }
}
