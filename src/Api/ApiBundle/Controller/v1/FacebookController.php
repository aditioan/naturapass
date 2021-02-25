<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 08/07/14
 * Time: 09:36
 */

namespace Api\ApiBundle\Controller\v1;

use Api\ApiBundle\Form\Handler\RegistrationFormHandler;
use Api\ApiBundle\Form\Type\FacebookRegistrationFormType;
use FOS\RestBundle\Util\Codes;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FacebookController extends ApiRestController
{

    /**
     * FR : Retourne l'utilisateur Facebook avec les identifiants passés en paramètre
     * EN : Returns the Facebook user with credentials passed in parameter
     *
     * GET /facebook/user?fid=1392349843
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"UserDetail"})
     */
    public function getFacebookUserAction(Request $request)
    {
        $this->authorize();

        if ($fid = $request->query->get('fid', false)) {
            $user = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:User')->findOneBy(array(
                'facebook_id' => $fid
            ));

            if ($user instanceof User) {
                return $this->view(array('user' => $this->getFormatUser($user, true), Codes::HTTP_OK));
            }

            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.nonexistent'));
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * FR : Inscris un utilisateur avec les informations Facebook
     * EN : Join a Facebook user with the information
     *
     * POST /facebooks/users
     *
     * Content-Type: form-data
     *      user[courtesy] = 0 => Indéfini, 1 => Monsieur, 2 => Madame
     *      user[lastname] = "VALOT"
     *      user[firstname] = "Vincent"
     *      user[email] = "v.valot@e-conception.fr"
     *      user[photo][file] = Données de fichier
     *      user[facebook_id] = ID Facebook
     *
     * @View(serializerGroups={"UserLess"})
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function postFacebookUserAction(Request $request)
    {

        $user = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:User')->findByEmail(
            $request->request->get('user[email]', false, true)
        );

        if ($user) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.email'));
        }

        $form = $this->createForm(
            new FacebookRegistrationFormType($this->getSecurityTokenStorage(), $this->container), new User(), array('csrf_protection' => false)
        );
        $formHandler = new RegistrationFormHandler($form, $request, $this->getDoctrine()->getManager());

        if ($process = $formHandler->process()) {
            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject(
                    $this->get('translator')->trans('user.register_without_confirmation.subject', array(), $this->container->getParameter("translation_name") . 'email')
                )
                ->setFrom($this->get('translator')->trans('user.register_without_confirmation.from', array(), $this->container->getParameter("translation_name") . 'email'))
                ->setTo($process->getEmail())
                ->addBcc($this->container->getParameter("email_bcc"))
                ->setBody(
                    $this->get('templating')->render(
                        'NaturaPassEmailBundle:User:register_api.html.twig', array(
                            'fullname' => $process->getFullName()
                        )
                    )
                );
            $this->get('mailer')->send($message);

            return $this->view(array('user_id' => $process->getId()), Codes::HTTP_CREATED);
        }

        return $this->view($form->getErrors(true), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * FR : Mets à jour les informations facebook d'un utilisateur connecté
     * EN : Upgrade facebook informations of a user connected
     *
     * @param Request $request
     *
     * PUT  /facebook/user
     *
     * Content-Type: form-data
     *      user[facebook_id] = 0123456
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function putFacebookUserAction(Request $request)
    {
        $this->authorize();

        if ($fid = $request->request->get('user[facebook_id]', false, true)) {
            if (!$this->getUser()->getFacebookId()) {
                $this->getUser()->setFacebookId($fid);

                $this->getDoctrine()->getManager()->persist($this->getUser());
                $this->getDoctrine()->getManager()->flush();
            }

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * FR : Mets à jour les informations facebook d'un utilisateur grace à son email
     * EN : Upgrade facebook informations of a user by his email
     *
     * @param Request $request
     *
     * PUT  /facebook/email
     *
     * Content-Type: form-data
     *      user[fid] = 0123456
     *      user[email] = n.mendez@e-conception.fr
     *
     * @return \FOS\RestBundle\View\View
     *
     * @throws HttpException
     */
    public function putFacebookEmailAction(Request $request)
    {
        if ($request->request->get('user[fid]', false, true) && $request->request->get('user[email]', false, true)) {
            $fid = $request->request->get('user[fid]', false, true);
            $email = $request->request->get('user[email]', false, true);
            $user = $this->getDoctrine()->getManager()->getRepository('NaturaPassUserBundle:User')->findByEmail($email);
            if (!$user) {
                throw new HttpException(Codes::HTTP_NOT_FOUND, $this->message('errors.user.email_inexistant'));
            } else {
                $user = $user[0];
            }
            $user->setFacebookId($fid);

            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * FR : Mets à jour l'information Facebook d'une publication
     * EN : Upgrade Facebook informations of a publication
     *
     * @param Publication $publication
     * @param string $fid
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function putFacebookPublicationAction(Publication $publication, $fid)
    {
        $this->authorize($publication->getOwner());

        $publication->setFacebookId($fid);

        $this->getDoctrine()->getManager()->persist($this->getUser());
        $this->getDoctrine()->getManager()->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

}
