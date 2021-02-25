<?php

namespace NaturaPass\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use NaturaPass\UserBundle\Entity\Device;
use NaturaPass\UserBundle\Entity\UserDevice;
use NaturaPass\UserBundle\Form\Type\ParametersFormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\UserBundle\Controller\ProfileController as BaseController;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserFriend;
use NaturaPass\MainBundle\Entity\Sharing;
use \NaturaPass\UserBundle\Form\Type\ProfileGeneralFormType;
use NaturaPass\UserBundle\Form\Type\ProfilePhotoFormType;
use NaturaPass\UserBundle\Form\Handler\ProfileFormHandler;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\PublicationBundle\Form\Type\PublicationFormType;

class ProfileController extends BaseController
{

    public function verifyDeviceAction($token)
    {
        $manager = $this->container->get('doctrine')->getManager();

        $userDevice = $manager->getRepository('NaturaPassUserBundle:UserDevice')->findOneBy(array(
            'owner' => $this->container->get('security.token_storage')->getToken()->getUser(),
            'verificationToken' => $token
        ));

        if ($userDevice instanceof UserDevice) {
            $userDevice->setVerificationToken(null)
                ->setVerified(true);

            $manager->persist($userDevice);
            $manager->flush();
        }

        return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Profile:verifyDevice.html.twig', array(
            'existent' => $userDevice instanceof UserDevice,
            'verified' => $userDevice instanceof UserDevice ? $userDevice->isVerified() : false,
            'device' => $userDevice instanceof UserDevice ? $userDevice->getDevice() : false
        ));
    }

    public function removeAction(Request $request)
    {
        if ($request->getMethod() === 'POST' && ($password = $request->request->get('user[password]', false, true))) {

            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            if ($user->getPassword() === $this->container->get('naturapass_sha1salted.encoder')->encodePassword($password)) {
                $entities = array();
                $manager = $this->container->get('doctrine')->getManager();

                $entities = array_merge($entities, $manager->getRepository('NaturaPassMessageBundle:Message')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:UserFriend')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:Invitation')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:UserMedia')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassUserBundle:UserFriend')->findByFriend($user));

                $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:PublicationCommentAction')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:PublicationComment')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:PublicationAction')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassPublicationBundle:Publication')->findByOwner($user));

                $entities = array_merge($entities, $manager->getRepository('NaturaPassNotificationBundle:NotificationReceiver')->findByReceiver($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassNotificationBundle:AbstractNotification')->findBySender($user));

                $entities = array_merge($entities, $manager->getRepository('NaturaPassLoungeBundle:LoungeMessage')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassLoungeBundle:LoungeUser')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassLoungeBundle:Lounge')->findByOwner($user));

                $entities = array_merge($entities, $manager->getRepository('NaturaPassGroupBundle:GroupMessage')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGroupBundle:GroupUser')->findByUser($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGroupBundle:Group')->findByOwner($user));

                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Edge')->findByFrom($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Edge')->findByTo($user));

                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Recommendation')->findByOwner($user));
                $entities = array_merge($entities, $manager->getRepository('NaturaPassGraphBundle:Recommendation')->findByTarget($user));

                $entities = array_merge($entities, $manager->getRepository('NaturaPassMessageBundle:Message')->findByOwner($user));

                foreach ($entities as $entity) {
                    $manager->remove($entity);
                }

                $manager->remove($user);

                $manager->flush();

                return new RedirectResponse($this->container->get('router')->generate('naturapass_main_homepage'));
            } else {
                return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Profile:remove.html.twig', array('passwordError' => true));
            }
        }

        return $this->container->get('templating')->renderResponse('NaturaPassUserBundle:Profile:remove.html.twig');
    }

    /**
     * Edition des paramêtres
     */
    public function parametersAction()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $form = $this->container->get('form.factory')->create(
            new ParametersFormType($this->container->get('security.token_storage')), $user->getParameters()
        );

        /**
         * @var \Doctrine\Common\Persistence\ObjectManager $manager
         */
        $manager = $this->container->get('doctrine')->getManager();

        $emails = $manager->getRepository('NaturaPassEmailBundle:EmailModel')->findBy(array(), array('order' => 'ASC'));

        $metadata = $manager->getMetadataFactory()->getMetadataFor('NaturaPass\NotificationBundle\Entity\AbstractNotification');

        $smartphone = (count($user->getDevices()) > 0) ? true : false;

        return $this->container->get('templating')->renderResponse(
            'NaturaPassUserBundle:Profile:angular.parameters.html.twig',
            array(
                'form' => $form->createView(),
                'emails' => $emails,
                'smartphone' => $smartphone,
                'notifications' => array_keys($metadata->discriminatorMap),
            )
        );
    }

    /**
     * Edit the user
     */
    public function editAction()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!is_object($user) && !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $formGeneral = $this->container->get('form.factory')->create(
            new ProfileGeneralFormType($this->container->get('security.token_storage'), $this->container), $user
        );
        $handlerGeneral = new ProfileFormHandler($formGeneral, $this->container->get('request'), $this->container->get(
            'doctrine'
        )->getManager());

        $formPhoto = $this->container->get('form.factory')->create(
            new ProfilePhotoFormType($this->container->get('security.token_storage'), $this->container), $user
        );
        $handlerPhoto = new ProfileFormHandler($formPhoto, $this->container->get('request'), $this->container->get(
            'doctrine'
        )->getManager());

        $formPassword = $this->container->get('fos_user.change_password.form');
        $handlerPassword = $this->container->get('fos_user.change_password.form.handler');

        $tab = null;

        if ($this->container->get('request')->request->has('user')) {
            if ($handlerGeneral->process(false)) {
                $this->container->get('session')->getFlashBag()->add(
                    'success', $this->container->get('translator')->trans('validation.general', array(), $this->container->getParameter("translation_name") . 'user')
                );
            }
            $tab = 'general';
        }

        if ($this->container->get('request')->request->has('user_photo')) {
            if ($user = $handlerPhoto->process(false)) {
                $this->container->get('session')->getFlashBag()->add(
                    'success', $this->container->get('translator')->trans('validation.photo', array(), $this->container->getParameter("translation_name") . 'user')
                );

                return new RedirectResponse($this->container->get('router')->generate('fos_user_profile_edit'));
            }
            $tab = 'photo';
        }
        if ($this->container->get('request')->request->has('fos_user_change_password_form')) {
            if ($handlerPassword->process($user)) {
                $this->container->get('session')->getFlashBag()->add(
                    'success', $this->container->get('translator')->trans('validation.password', array(), $this->container->getParameter("translation_name") . 'user')
                );
            }
            $tab = 'password';
        }

        return $this->container->get('templating')->renderResponse(
            'NaturaPassUserBundle:Profile:edit.html.twig', array(
                'formGeneral' => $formGeneral->createView(),
                'formPhoto' => $formPhoto->createView(),
                'formPassword' => $formPassword->createView(),
                'tab' => $tab
            )
        );
    }

    /**
     * Show the user
     *
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     */
    public function showAction(User $user = null)
    {
        $form =  $this->container->get('form.factory')->create(
            new PublicationFormType($this->container->get('security.token_storage'), $this->container)
        );
        
        $formEdit =  $this->container->get('form.factory')->create(
         new PublicationFormType($this->container->get('security.token_storage'), $this->container, '_edit')
        );

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Profile:show.html.' . $this->container->getParameter('fos_user.template.engine'),
            array(
                'mediaForm' => $form->createView(),
                 'publicationForm' => $form->createView(),
                 'editPublicationForm' => $form->createView(),
                 'editMediaPublicationForm' => $formEdit->createView(),
                 'user' => $user instanceof User ? $user : $this->container->get('security.token_storage')->getToken()->getUser(),
            )
        );
    }
}
