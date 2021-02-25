<?php

namespace Admin\NewsBundle\Controller;

use Admin\NewsBundle\Entity\News;
use Admin\NewsBundle\Entity\Slide;
use Admin\NewsBundle\Entity\SlideMedia;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Admin\NewsBundle\Form\Type\NewsType;
use Admin\NewsBundle\Form\Handler\NewsHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class DefaultController extends Controller {

    public function indexAction() {
        return $this->render('AdminNewsBundle:Default:angular.index-news.html.twig');
    }

    public function addAction(Request $request) {
        $form = $this->createForm(new NewsType($this->container), new News());
        $handler = new NewsHandler($form, $request, $this->getDoctrine()->getManager());
        if ($news = $handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_news_homepage'));
        }
        return $this->render('AdminNewsBundle:Default:angular.add.html.twig', array(
                    'form' => $form->createView(),
                    'ajout' => 1
        ));
    }

    /**
     * @param \Admin\NewsBundle\Entity\News $new
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @ParamConverter("news", class="AdminNewsBundle:News")
     */
    public function editAction($news, Request $request) {
        $form = $this->createForm(new NewsType($this->container), $news);
        $handler = new NewsHandler($form, $request, $this->getDoctrine()->getManager());

        if ($handler->process()) {
            return new RedirectResponse($this->get('router')->generate('admin_news_homepage'));
        }
        return $this->render('AdminNewsBundle:Default:angular.add.html.twig', array(
                    'form' => $form->createView(),
                    'ajout' => 0
        ));
    }

    public function slidesAction(Request $request) {
        if ($request->getMethod() === 'POST') {
            $slide = new Slide();
            $media = new SlideMedia();

            $media->setFile($request->files->get('slide[media][file]', false, true))
                    ->setName($request->request->get('slide[media][name]', false, true));

            $slide
                    ->setActive($request->request->get('slide[active]', false, true) == 'on' ? true : false)
                    ->setMedia($media);

            $this->getDoctrine()->getManager()->persist($slide);
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('AdminNewsBundle:Default:slides.html.twig');
    }

}
