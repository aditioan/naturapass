<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 10/06/14
 * Time: 10:23
 */

namespace NaturaPass\UserBundle\Controller;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ResettingController extends \FOS\UserBundle\Controller\ResettingController
{

    public function resendConfirmationAction(Request $request)
    {

        if ($request->getMethod() === 'POST' && $request->request->has('username')) {

            $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($request->request->get('username'));

            if (null === $user || ($user && $user->isEnabled())) {
                return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:resendConfirmation.html.' . $this->getEngine(), array('invalid_username' => $request->request->get('username')));
            }

            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());

            $message = \Swift_Message::newInstance()
                ->setContentType("text/html")
                ->setSubject($this->container->get('translator')->trans('user.register_confirmation.subject', array(), $this->container->getParameter("translation_name") . 'email'))
                ->setFrom($this->container->get('translator')->trans('user.register_confirmation.from', array(), $this->container->getParameter("translation_name") . 'email'))
                ->setTo($user->getEmail())
                ->addBcc('suivi@naturapass.com')
                ->setBody($this->container->get('templating')->render('NaturaPassEmailBundle:User:registration.html.twig', array(
                    'fullname' => $user->getFullName(),
                    'link' => $user->getConfirmationToken()
                )));
            $this->container->get('mailer')->send($message);

            $this->container->get('fos_user.user_manager')->updateUser($user);

            $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());

            return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:checkEmail.html.twig', array(
                'user' => $user,
            ));

            //$url = $this->container->get('router')->generate('fos_user_registration_check_email');
            //return new RedirectResponse($url);
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:resendConfirmation.html.' . $this->getEngine());
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $session = $this->container->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:checkEmail.html.' . $this->getEngine(), array(
            'email' => $email,
        ));
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction()
    {
        $username = $this->container->get('request')->request->get('username');

        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

        if (null === $user) {
            return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.' . $this->getEngine(), array('invalid_username' => $username));
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:passwordAlreadyRequested.html.' . $this->getEngine());
        }

        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $this->container->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));

        $message = \Swift_Message::newInstance()
            ->setContentType("text/html")
            ->setSubject($this->container->get('translator')->trans('user.changepassword.subject', array(), $this->container->getParameter("translation_name") . 'email'))
            ->setFrom($this->container->get('translator')->trans('user.changepassword.from', array(), $this->container->getParameter("translation_name") . 'email'))
            ->setTo($user->getEmail())
            ->addBcc('suivi@naturapass.com')
            ->setBody($this->container->get('templating')->render('NaturaPassEmailBundle:User:change-password.html.twig', array(
                'fullname' => $user->getFullName(),
                'lien' => $user->getConfirmationToken()
            )));
        $this->container->get('mailer')->send($message);

        $this->container->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
        //$this->container->get('fos_user.mailer')->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->container->get('fos_user.user_manager')->updateUser($user);

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:checkEmail.html.' . $this->getEngine(), array(
            'email' => $user->getEmail(),
        ));
    }
}
