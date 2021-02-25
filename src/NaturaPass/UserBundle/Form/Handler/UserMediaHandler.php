<?php

namespace NaturaPass\UserBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use NaturaPass\UserBundle\Entity\UserMedia;
use NaturaPass\MediaBundle\Entity\Tag;

/**
 * Description of PublicationHandler
 *
 * @author vincentvalot
 */
class UserMediaHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    public function process() {
        if ($this->request->getMethod() === 'POST') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($this->form->getData());

                return true;
            }
        }

        return false;
    }

    public function onSuccess(UserMedia $media) {
        if ($tags = $file = $this->form->get('tags')->getData()) {
            $repo = $this->manager->getRepository('NaturaPassMediaBundle:Tag');

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
        }

        if ($sharingForm = $this->form->get('sharing')) {
            if ($withouts = $sharingForm->get('withouts')->getData()) {
                $repo = $this->manager->getRepository('NaturaPassUserBundle:User');

                $sharing = $sharingForm->getData();

                if (!is_array($withouts)) {
                    $withouts = explode(',', $withouts);
                }

                foreach ($withouts as $id) {
                    $user = $repo->find($id);

                    if ($user) {
                        $sharing->addWithout($user);
                    }
                }
            }
        }

        $this->manager->persist($media);
        $this->manager->flush();
    }

}