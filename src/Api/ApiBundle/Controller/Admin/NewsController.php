<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 09/07/14
 * Time: 15:09
 */

namespace Api\ApiBundle\Controller\Admin;

use Admin\NewsBundle\Entity\News;
use Admin\NewsBundle\Entity\Slide;
use Api\ApiBundle\Controller\v1\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\NewsSerialization;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class NewsController extends ApiRestController
{

    /**
     * POST /admin/news
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"NewsDetail", "NewsLess"})
     */
    public function postNewsAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $news = new News();

        $news->setFr($request->request->get('news[fr]', '', true))
            ->setEn($request->request->get('news[en]', '', true))
            ->setDe($request->request->get('news[de]', '', true))
            ->setEs($request->request->get('news[es]', '', true))
            ->setDate(\DateTime::createFromFormat('d/m/Y', $request->request->get('news[date]', '', true)))
            ->setActive($request->request->get('news[active]', false, true));

        $manager = $this->getDoctrine()->getManager();

        $manager->persist($news);
        $manager->flush();

        return $this->view(array('news' => $news), Codes::HTTP_CREATED);
    }

    /**
     *
     *
     * @param News $news
     * @param int $active
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("news", class="AdminNewsBundle:News")
     */
    public function putNewsActiveAction(News $news, $active)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $news->setActive($active);

        $manager = $this->getDoctrine()->getManager();

        $manager->persist($news);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getNewsAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = urldecode($request->query->get('filter', ''));

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('n')
            ->from('AdminNewsBundle:News', 'n')
            ->where('n.title LIKE :name')
            ->orderBy('n.date', 'DESC')
            ->setParameter('name', '%' . $filter . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();


        return $this->view(array('news' => NewsSerialization::serializeNews($results)), Codes::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getNewsActiveAction(Request $request)
    {
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('n')
            ->from('AdminNewsBundle:News', 'n')
            ->where('n.active = 1')
            ->orderBy('n.date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();


        return $this->view(array('news' => NewsSerialization::serializeNews($results)), Codes::HTTP_OK);
    }

    /**
     * @param News $news
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("news", class="AdminNewsBundle:News")
     */
    public function deleteNewsAction(News $news)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($news);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * @param Slide $slide
     * @param int $active
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("slide", class="AdminNewsBundle:Slide")
     */
    public function putSlideActiveAction(Slide $slide, $active)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $slide->setActive($active);

        $manager = $this->getDoctrine()->getManager();

        $manager->persist($slide);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * @param Slide $slide
     * @param integer $sort
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("slide", class="AdminNewsBundle:Slide")
     */
    public function putSlideSortAction(Slide $slide, $sort)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $slide->setSort($sort);

        $manager = $this->getDoctrine()->getManager();

        $manager->persist($slide);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"NewsDetail", "NewsLess", "MediaDetail"})
     */
    public function getSlidesAction(Request $request)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $qb = $this->getDoctrine()->getRepository('AdminNewsBundle:Slide')->createQueryBuilder('s');

        $news = $qb->setFirstResult($request->request->get('offset'))
            ->setMaxResults($request->request->get('limit'))
            ->orderBy('s.created', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->view(array('slides' => $news), Codes::HTTP_OK);
    }

    /**
     * @param Slide $slide
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("slide", class="AdminNewsBundle:Slide")
     */
    public function deleteSlideAction(Slide $slide)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $manager = $this->getDoctrine()->getManager();

        $manager->remove($slide);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_NO_CONTENT);
    }

}
