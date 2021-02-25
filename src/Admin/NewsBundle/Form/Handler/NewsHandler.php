<?php

namespace Admin\NewsBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Admin\NewsBundle\Entity\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Admin\NewsBundle\Entity\NewsMedia;

/**
 * Description of NewsHandler
 *
 */
class NewsHandler {

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager) {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \Admin\NewsBundle\Entity\News
     */
    public function process() {
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'PUT') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    /**
     * @param \Admin\NewsBundle\Entity\News $news
     * @return \Admin\NewsBundle\Entity\News $news
     */
    public function onSuccess(News $news) {
        if ($photo = $this->request->files->get('news[photo][file]', false, true)) {
            $media = new NewsMedia();
            $media->setFile($photo);

            $this->manager->remove($news->getPhoto());

            $news->setPhoto($media);
        }
        $this->manager->persist($news);
        $this->manager->flush();

        return $news;
    }

}
