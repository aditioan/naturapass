<?php

namespace NaturaPass\MainBundle\Controller;

use Doctrine\ORM\Query\AST\Join;
use NaturaPass\UserBundle\Controller\RegistrationController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Api\ApiBundle\Controller\v2\Serialization\NewsSerialization;
use Doctrine\Common\Collections\ArrayCollection;

class DefaultController extends Controller
{

    private function renderViewWithRegisterForm($view, array $parameters = array()) {
        $form = $this->container->get('fos_user.registration.form');
        return $this->render($view, array_merge(array('form' => $form->createView()), $parameters));
    }

    public function channelAction()
    {
        $cache_expire = 60 * 60 * 24 * 365;
        header("Pragma: public");
        header("Cache-Control: maxage=" . $cache_expire);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_expire) . ' GMT');

        return $this->render('NaturaPassMainBundle:Default:channel.html.twig');
    }

    public function cguAction()
    {
        return $this->render('NaturaPassMainBundle:Default:cgu.html.twig');
    }

    public function cgvAction()
    {
        return $this->render('NaturaPassMainBundle:Default:cgv.html.twig');
    }

    public function privacyAction()
    {
        return $this->render('NaturaPassMainBundle:Default:privacy.html.twig');
    }

    public function downloadAction()
    {
        $files = array("files" => array(
                "Android v2 prod" => array("naturapassAndroid-release-prod.apk", ""),
                "Android v2 dev"  => array("naturapassAndroid-release-dev.apk", "")
        ));
        foreach ($files["files"] as $name => $array) {
            $files["files"][$name][1] = date("d/m/Y H:i:s", filemtime($_SERVER["DOCUMENT_ROOT"] . "/uploads/applications/" . $files["files"][$name][0]));
        }
        return $this->render('NaturaPassMainBundle:Default:download.html.twig', $files);
    }

    public function downloadRoadAction()
    {
        $files = array("files" => array(
                "Android" => array("RoadBuildingAndroid-release-dev.apk", "")
        ));
        foreach ($files["files"] as $name => $array) {
            $files["files"][$name][1] = date("d/m/Y H:i:s", filemtime($_SERVER["DOCUMENT_ROOT"] . "/uploads/applications/" . $files["files"][$name][0]));
        }
        return $this->render('NaturaPassMainBundle:Default:download.html.twig', $files);
    }

    public function downloadPiquandAction()
    {
        $files = array("files" => array(
                "Android" => array("PiquandTP-release.apk", "")
        ));
        foreach ($files["files"] as $name => $array) {
            $files["files"][$name][1] = date("d/m/Y H:i:s", filemtime($_SERVER["DOCUMENT_ROOT"] . "/uploads/applications/" . $files["files"][$name][0]));
        }
        return $this->render('NaturaPassMainBundle:Default:download.html.twig', $files);
    }

    public function downloadIcAction()
    {
        $files = array("files" => array(
                "Android" => array("interactions-citoyennes-release.apk", "")
        ));
        foreach ($files["files"] as $name => $array) {
            $files["files"][$name][1] = date("d/m/Y H:i:s", filemtime($_SERVER["DOCUMENT_ROOT"] . "/uploads/applications/" . $files["files"][$name][0]));
        }
        return $this->render('NaturaPassMainBundle:Default:download.html.twig', $files);
    }

    public function downloadJbAction()
    {
        $files = array("files" => array("Android" => array("JaimeBourg-release.apk", "")));
        foreach ($files["files"] as $name => $array) {
            $files["files"][$name][1] = date("d/m/Y H:i:s", filemtime($_SERVER["DOCUMENT_ROOT"] . "/uploads/applications/" . $files["files"][$name][0]));
        }
        return $this->render('NaturaPassMainBundle:Default:download.html.twig', $files);
    }

    public function downloadSmAction()
    {
        $files = array("files" => array("Android" => array("StMars-release.apk", "")));
        foreach ($files["files"] as $name => $array) {
            $files["files"][$name][1] = date("d/m/Y H:i:s", filemtime($_SERVER["DOCUMENT_ROOT"] . "/uploads/applications/" . $files["files"][$name][0]));
        }
        return $this->render('NaturaPassMainBundle:Default:download.html.twig', $files);
    }

    public function markerAction()
    {
        $getColor = filter_input(INPUT_GET, 'color', FILTER_SANITIZE_STRING);
        $getForme = filter_input(INPUT_GET, 'forme', FILTER_SANITIZE_STRING);
        $getDrag = filter_input(INPUT_GET, 'drag', FILTER_VALIDATE_BOOLEAN);
        $getDirect = filter_input(INPUT_GET, 'direct', FILTER_VALIDATE_BOOLEAN);
        $getBg = filter_input(INPUT_GET, 'bg', FILTER_VALIDATE_BOOLEAN);
        $id_publication = filter_input(INPUT_GET, 'id_publication', FILTER_VALIDATE_INT);
        $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $img = filter_input(INPUT_GET, 'img', FILTER_VALIDATE_BOOLEAN);
        $getSmall = filter_input(INPUT_GET, 'small', FILTER_VALIDATE_BOOLEAN);
        if (isset($id_publication)) {
            $repo = $this->getDoctrine()->getManager()->getRepository('NaturaPassPublicationBundle:Publication');
            $element = array("publication" => $repo->find($id_publication), "type" => $type);
        } else {
            $element = array("picto" => $img);
        }
        $return = \Api\ApiBundle\Controller\v2\ApiRestController::getMarker($getColor, $getForme, $element, $getDrag, $getDirect, $getBg, $getSmall);
        if ($getDirect) {
            $headers = array(
                    'Content-Type'        => 'image/png',
                    'Content-Disposition' => 'inline; filename="marker"');
            return new Response($return, 200, $headers);
        } else {
            return new Response($return);
        }
    }

    public function mapOldAction()
    {
        $this->get('session')->remove('naturapass_map/positions_loaded');

        $parameters = array(
                'mediaForm'                => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                'publicationForm'          => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                'editPublicationForm'      => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                'editMediaPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container, '_edit'))->createView(),
                'tmpUploadDir'             => uniqid()
        );

        return $this->render('NaturaPassMainBundle:Default:angular.map.html.twig', $parameters);
    }

    public function mapNewAction()
    {
        $this->get('session')->remove('naturapass_map/positions_loaded');

        $parameters = array(
                'mediaForm'                => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                'publicationForm'          => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                'editPublicationForm'      => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                'editMediaPublicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container, '_edit'))->createView(),
                'tmpUploadDir'             => uniqid()
        );

        return $this->render('NaturaPassMainBundle:Default:angular.map-new.html.twig', $parameters);
    }

    public function printableMapAction()
    {
        $this->get('session')->remove('naturapass_map/positions_loaded');

        $parameters = array(
                'mediaForm'       => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
                'publicationForm' => $this->createForm(new PublicationFormType($this->get('security.token_storage'), $this->container))->createView(),
        );

        return $this->render('NaturaPassMainBundle:Default:angular.printable-map.html.twig', $parameters);
    }

    public function indexAction()
    {

        $this->container->get('session')->remove('user.register/RegisterGameFair');
        $this->container->get('session')->remove('user.register/connectGameFair');
        $securityContext = $this->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $controller = new \NaturaPass\PublicationBundle\Controller\DefaultController();
            $controller->setContainer($this->container);

            return $controller->indexAction();
        } else {
            $controller = new RegistrationController();
            $controller->setContainer($this->container);

            return $controller->registerAction();
        }
    }

    public function gameFairAction()
    {
        $securityContext = $this->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $controller = new \NaturaPass\PublicationBundle\Controller\DefaultController();
            $controller->setContainer($this->container);

            return $controller->indexAction();
        } else {
            $controller = new RegistrationController();
            $controller->setContainer($this->container);

            return $controller->registerGameFairAction();
        }
    }

    public function searchAction(Request $request)
    {
        return $this->render('NaturaPassMainBundle:Default:angular.search.html.twig', array(
                'search' => $request->query->get('q', '')
        ));
    }

    public function siteChasseAction()
    {
        return $this->render('NaturaPassMainBundle:Default:site-chasse.html.twig');
    }

    public function geolocChasseAction()
    {
        return $this->render('NaturaPassMainBundle:Default:geolocalisation-groupe-chasse.html.twig');
    }

    public function territoireChasseAction()
    {
        return $this->render('NaturaPassMainBundle:Default:territoire-espace-chasse.html.twig');
    }

    public function memoireChasseAction()
    {
        return $this->render('NaturaPassMainBundle:Default:memoire-groupe-chasse.html.twig');
    }

    public function groupeChasseAction()
    {
        return $this->render('NaturaPassMainBundle:Default:groupe-chasse.html.twig');
    }

    public function groupeEspaceChasseAction()
    {
        return $this->render('NaturaPassMainBundle:Default:groupe-espace-chasse.html.twig');
    }

    /**
     * @param \Admin\GameBundle\Entity\Game $game
     *
     * @throws  HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("game", class="AdminGameBundle:Game")
     */
    public function concoursDetailAction($game)
    {
        $securityContext = $this->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $controller = new RegistrationController();
            $controller->setContainer($this->container);

            return $controller->registerConcoursAction($game, 1);
        } else {
            $controller = new RegistrationController();
            $controller->setContainer($this->container);

            return $controller->registerConcoursAction($game, 0);
        }
        //        return $this->render('NaturaPassMainBundle:Default:concours-detail.html.twig', array(
        //                    'game' => $game
        //        ));
    }

    /**
     * @param \Admin\GameBundle\Entity\Game $game
     *
     * @throws  HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("game", class="AdminGameBundle:Game")
     */
    public function challengeInscriptionAction($game)
    {
        $securityContext = $this->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $controller = new RegistrationController();
            $controller->setContainer($this->container);

            return $controller->registerChallengeAction($game, 1);
        } else {
            $controller = new RegistrationController();
            $controller->setContainer($this->container);

            return $controller->registerChallengeAction($game, 0);
        }
        //        return $this->render('NaturaPassMainBundle:Default:concours-detail.html.twig', array(
        //                    'game' => $game
        //        ));
    }

    public function concoursListAction()
    {
        $securityContext = $this->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:concours-list.html.twig', array('connect' => 1));
        } else {
            return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:concours-list.html.twig', array('connect' => 0));
        }
    }

    public function partnersListAction()
    {
        return $this->render('NaturaPassMainBundle:Default:partners.html.twig');
    }

    public function murAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:mur.html.twig');
    }

    public function carteAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:carte.html.twig');
    }

    public function discussionsAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:discussions.html.twig');
    }

    public function groupesAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:groupes.html.twig');
    }

    public function agendaAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:agenda.html.twig');
    }

    public function amisAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:amis.html.twig');
    }

    public function naturapassAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:naturapass.html.twig');
    }

    public function jaimeAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:jaime.html.twig');
    }

    public function realisationsAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:realisations.html.twig');
    }

    public function applicationsAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:applications.html.twig');
    }

    public function partenaireAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:partenaire.html.twig');
    }

    public function pressAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:press.html.twig');
    }

    public function contactAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:contact.html.twig');
    }

    public function requestInfomationAction(Request $request)
    {
        $data = $request->request->get('requestInfomation');
        $opt['ssl']['verify_peer'] = FALSE;
        $opt['ssl']['verify_peer_name'] = FALSE;
        $this->get('swiftmailer.mailer.default.transport.real')->setStreamOptions($opt);
        $message = \Swift_Message::newInstance()
                                ->setContentType("text/html")
                                ->setSubject("Demande d'information")
                                ->setFrom($data['email'])
                                ->setTo("team@naturapass.com")
                                ->addBcc('aditioan.uny@gmail.com')
                                ->setBody($this->renderView('NaturaPassEmailBundle:Main:request-information.html.twig', array(
                                'user_fullname' => $data['firstname']." ".$data['lastname'],
                                'email' => $data['email'],
                                'email_body' => $data['message'],
                            )));
                            $this->get('mailer')->send($message);
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:success-contact.html.twig');
    }

    public function successContactAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:success-contact.html.twig');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }

    public function proAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:pro.html.twig');
    }

    public function devenirAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:devenir.html.twig');
    }

    public function sitemapAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:sitemap.html.twig');
    }

    public function mentionAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Default:mention.html.twig');
    }

    // Smartpages

    public function smartpageApplicationChasseAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Smartpage:application-chasse.html.twig');
    }

    public function smartpageApplicationDeChasseAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Smartpage:application-de-chasse.html.twig');
    }

    public function smartpageApplicationPourChasseurAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Smartpage:application-pour-chasseur.html.twig');
    }

    public function smartpageReseauChasseAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Smartpage:reseau-chasse.html.twig');
    }

    public function smartpageSiteChasseAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Smartpage:site-chasse.html.twig');
    }

    public function smartpageSiteDeChasseAction()
    {
        return $this->renderViewWithRegisterForm('NaturaPassMainBundle:Smartpage:site-de-chasse.html.twig');
    }

    public function rivolierAction()
    {
        return $this->render('NaturaPassMainBundle:Default:rivolier.html.twig');
    }

    public function rivolierSubscribeAction($id)
    {
        return $this->render('NaturaPassMainBundle:Default:rivolier_subscribe.html.twig', array(
            'id' => $id
        ));
    }
}
