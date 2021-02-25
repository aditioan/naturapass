<?php

namespace NaturaPass\PublicationBundle\Form\Handler;

use Admin\SentinelleBundle\Entity\Locality;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\MainBundle\Component\GeolocationService;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\MainBundle\Entity\Geolocation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use NaturaPass\PublicationBundle\Entity\Publication as Publication;
use NaturaPass\MediaBundle\Entity\Tag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Description of PublicationHandler
 *
 * @author vincentvalot
 */
class PublicationHandler
{

    protected $request;
    protected $form;
    protected $manager;
    protected $securityContext;
    protected $geolocationService;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager, TokenStorageInterface $securityContext, GeolocationService $geolocationService)
    {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
        $this->securityContext = $securityContext;
        $this->geolocationService = $geolocationService;
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

    public function onSuccess(Publication $publication)
    {
        $this->handleForm($publication);

        if ($this->request->getMethod() === 'PUT') {
            if (!$this->request->request->get('publication[geolocation]', false, true) && $publication->getGeolocation() instanceof Geolocation && $publication->getGeolocation()->getId()) {
                $this->manager->remove($publication->getGeolocation());

                $publication->setGeolocation(null);
            }
        }

        $geolocation = $publication->getGeolocation();
        if ($geolocation instanceof Geolocation && is_null($publication->getLocality())) {
            $publication->setLocality($this->geolocationService->findACity($geolocation, true, true));
        }

        $this->manager->persist($publication);
        $this->manager->flush();

        return $publication;
    }

    public function handleForm(Publication $publication)
    {

        if (($media = $this->form->get('media')) && ($tags = $media->get('tags')->getData())) {
            $repo = $this->manager->getRepository('NaturaPassMediaBundle:Tag');
            $media = $media->getData();
            $media->setTags(new ArrayCollection());

            if (!is_array($tags)) {
                $tags = explode(',', $tags);
            }

            foreach ($tags as $name) {
                $tag = $repo->findOneBy(array(
                    'name' => $name
                ));

                if (!$tag) {
                    $tag = new Tag();
                    $tag->setName($name);
                }

                $media->addTag($tag);
            }
            $publication->setMedia($media);
        }

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

        if ($groupForm = $this->form->get('groups')) {
            if ($groups = $groupForm->getData()) {
                $repo = $this->manager->getRepository('NaturaPassGroupBundle:Group');

                if (!is_array($groups)) {
                    $groups = explode(',', $groups);
                }

                $publication->removeAllGroups();

                $groups = array_unique($groups);

                foreach ($groups as $id) {
                    $group = $repo->find($id);
                    if (!is_null($group) && $group && $group->checkAllowAdd($publication->getOwner()) && $group->isSubscriber($this->securityContext->getToken()->getUser())) {
                        $publication->addGroup($group);
                    }
                }
            }
        }

	if ($userForm = $this->form->get('users')) {
            if ($users = $userForm->getData()) {
                $repo = $this->manager->getRepository('NaturaPassUserBundle:User');

                if (!is_array($users)) {
                    $users = explode(',', $users);
                }

                $publication->removeAllShareusers();

                $users = array_unique($users);

                foreach ($users as $id) {
                    $user = $repo->find($id);
//                    $publication->addShareuser($user);
			if(!is_null($user)){
                        $publication->addShareuser($user);
                    }
                }
            }
        }

        if ($huntForm = $this->form->get('hunts')) {
            if ($hunts = $huntForm->getData()) {
                $repo = $this->manager->getRepository('NaturaPassLoungeBundle:Lounge');

                if (!is_array($hunts)) {
                    $hunts = explode(',', $hunts);
                }

                $publication->removeAllHunts();

                $hunts = array_unique($hunts);

                foreach ($hunts as $id) {
                    $hunt = $repo->find($id);
                    if ($hunt && $hunt->isSubscriber($this->securityContext->getToken()->getUser())) {
                        $publication->addHunt($hunt);
                    }
                }
            }
        }
        if ($date = $this->form->get('date', false) && !is_null($publication->getDate())) {
            $publication->setCreated($publication->getDate());
        }

        $publication->setContent(SecurityUtilities::sanitize($publication->getContent()));
    }

}
