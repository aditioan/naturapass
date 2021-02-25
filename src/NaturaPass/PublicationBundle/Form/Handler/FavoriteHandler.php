<?php

namespace NaturaPass\PublicationBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\PublicationBundle\Entity\Favorite;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Description of FavoriteHandler
 *
 */
class FavoriteHandler
{

    protected $request;
    protected $form;
    protected $manager;
    protected $securityContext;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager, TokenStorageInterface $securityContext)
    {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
        $this->securityContext = $securityContext;
    }

    /**
     * Tells whether the form is valid or not
     *
     * @return bool
     */
    public function isFormValid()
    {
        if (!$this->form->isSubmitted()) {
            $this->form->handleRequest($this->request);
        }

        return $this->form->isValid();
    }

    public function process()
    {
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'PUT') {
            if (!$this->form->isSubmitted()) {
                $this->form->handleRequest($this->request);
            }

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    public function onSuccess(Favorite $favorite)
    {
        $this->handleForm($favorite);

        return $favorite;
    }

    public function handleForm(Favorite $favorite)
    {

        if ($sharingForm = $this->form->get('sharing')) {
            if ($withouts = $sharingForm->get('withouts')->getData()) {
                $repo = $this->manager->getRepository('NaturaPassUserBundle:User');

                $sharing = $sharingForm->getData();

                if (!is_array($withouts)) {
                    $withouts = explode(',', $withouts);
                }

                $sharing->removeAllWithout();

                foreach ($withouts as $id) {
                    $user = $repo->find($id);

                    if ($user) {
                        $sharing->addWithout($user);
                    }
                }
            }
        }
        $favorite->removeAllGroups();
        if ($groupForm = $this->form->get('groups')) {
            if ($groups = $groupForm->getData()) {
                $repo = $this->manager->getRepository('NaturaPassGroupBundle:Group');

                if (!is_array($groups)) {
                    $groups = explode(',', $groups);
                }

                $favorite->removeAllGroups();

                $groups = array_unique($groups);

                foreach ($groups as $id) {
                    $group = $repo->find($id);
                    if ($group && $group->isSubscriber($this->securityContext->getToken()->getUser())) {
                        $favorite->addGroup($group);
                    }
                }
            }
        }

	$favorite->removeAllShareusers();
        if ($userForm = $this->form->get('users')) {
            if ($users = $userForm->getData()) {
                $repo = $this->manager->getRepository('NaturaPassUserBundle:User');

                if (!is_array($users)) {
                    $users = explode(',', $users);
                }

                $favorite->removeAllShareusers();

                $users = array_unique($users);

                foreach ($users as $id) {
                    $user = $repo->find($id);
                    if ($user) {
                        $favorite->addShareuser($user);
                    }
                }
            }
        }

        $favorite->removeAllHunts();
        if ($huntForm = $this->form->get('hunts')) {
            if ($hunts = $huntForm->getData()) {
                $repo = $this->manager->getRepository('NaturaPassLoungeBundle:Lounge');

                if (!is_array($hunts)) {
                    $hunts = explode(',', $hunts);
                }

                $favorite->removeAllHunts();

                $hunts = array_unique($hunts);

                foreach ($hunts as $id) {
                    $hunt = $repo->find($id);
                    if ($hunt && $hunt->isSubscriber($this->securityContext->getToken()->getUser())) {
                        $favorite->addHunt($hunt);
                    }
                }
            }
        }
    }

}
