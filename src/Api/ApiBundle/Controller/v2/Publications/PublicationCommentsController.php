<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 28/07/15
 * Time: 15:20
 */

namespace Api\ApiBundle\Controller\v2\Publications;

use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\PublicationSerialization;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GraphBundle\Entity\Edge;
use NaturaPass\MainBundle\Component\Security\SecurityUtilities;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationCommentedNotification;
use NaturaPass\NotificationBundle\Entity\Publication\PublicationSameCommentedNotification;
use NaturaPass\PublicationBundle\Entity\Publication;
use NaturaPass\PublicationBundle\Entity\PublicationAction;
use NaturaPass\PublicationBundle\Entity\PublicationComment;
use NaturaPass\PublicationBundle\Entity\PublicationCommentAction;
use NaturaPass\PublicationBundle\Form\Type\PublicationCommentFormType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublicationCommentsController extends ApiRestController {

    /**
     * Récupère les likes utilisateurs
     *
     * GET /v2/comment/{comment_id}/comment/likes
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     */
    public function getCommentLikesAction(PublicationComment $comment)
    {
        return $this->view(
            array(
                'likes' => PublicationSerialization::serializePublicationLikes(
                    $comment->getActions(),
                    $this->getUser()
                )
            ),
            Codes::HTTP_OK
        );
    }

    /**
     * Ajoute un commentaire sur une publication
     *
     * POST /v2/publications/{publication}/comments
     *
     * @param Request $request
     * @param \NaturaPass\PublicationBundle\Entity\Publication
     * @return \FOS\RestBundle\View\View
     * @throws HttpException
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function postCommentAction(Request $request, Publication $publication) {
        $this->authorize();
        $this->authorizePublication($publication);

        $comment = new PublicationComment();
        $comment->setOwner($this->getUser())
            ->setPublication($publication);

        $form = $this->createForm(new PublicationCommentFormType(), $comment);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $manager = $this->getDoctrine()->getManager();

            $comment->setContent(SecurityUtilities::sanitize($comment->getContent()));

            $manager->persist($comment);
            $manager->flush();

            if ($this->getUser()->getId() != $publication->getOwner()->getId()) {

//                $this->delay(function() use ($publication) {
//                    $this->getGraphService()->generateEdge(
//                        $this->getUser(),
//                        $publication->getOwner(),
//                        Edge::PUBLICATION_COMMENTED,
//                        $publication->getId()
//                    );
//                });

                $this->getNotificationService()->queue(
                    new PublicationCommentedNotification($publication, $comment), $publication->getOwner()
                );

                $this->getEmailService()->generate(
                    'publication.comment', array(), array($publication->getOwner()), 'NaturaPassEmailBundle:Publication:comment-email.html.twig', array('fullname' => $this->getUser()->getFullname(), 'comment' => $publication->getFirstWordLastComment(), 'publication' => $publication)
                );
            }

            $owners = $publication->getNLastOwnerComment();
            $owners->removeElement($this->getUser());
            $owners->removeElement($publication->getOwner());

            foreach ($owners as $owner) {
                $this->getNotificationService()->queue(
                    new PublicationSameCommentedNotification($publication, $comment), $owner
                );

                $this->getEmailService()->generate(
                    'publication.same_comment', array(), array($owner), 'NaturaPassEmailBundle:Publication:same-comment-email.html.twig', array('fullname' => $this->getUser()->getFullname(), 'owner' => $publication->getOwner()->getFullName(), 'date' => $publication->getCreated()->format('d/m/Y'), 'comment' => $publication->getFirstWordLastComment(), 'publication' => $publication)
                );
            }

            return $this->view(array(
                'comment' => PublicationSerialization::serializePublicationComment($comment, $this->getUser()),
'comment1' => PublicationSerialization::serializePublicationComment($comment, $this->getUser())),
                Codes::HTTP_CREATED
            );
        }

        return $this->view($form->getErrors(), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Ajoute un like utilisateur sur un commentaire de publication
     *
     * POST /v2/publications/{comment}/comment/like
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     */
    public function postCommentLikeAction(PublicationComment $comment) {
        $this->authorize();
        $this->authorizePublication($comment->getPublication());

        $manager = $this->getDoctrine()->getManager();

        $like = $manager->getRepository('NaturaPassPublicationBundle:PublicationCommentAction')->findOneBy(
            array(
                'user' => $this->getUser(),
                'comment' => $comment
            )
        );

        if (!$like) {
            $like = new PublicationCommentAction;
            $like->setComment($comment)
                ->setUser($this->getUser());
        }

        $like->setState(PublicationAction::STATE_LIKE);

        $manager->persist($like);
        $manager->flush();

        return $this->view(
            array('likes' => $comment->getActions(PublicationCommentAction::STATE_LIKE)->count()),
            Codes::HTTP_OK
        );
    }

    /**
     * Updating a comment by its owner
     *
     * PUT /v2/publications/{comment}/comment
     *
     * @param \NaturaPass\PublicationBundle\Entity\PublicationComment $comment
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("comment", class="NaturaPassPublicationBundle:PublicationComment")
     */
    public function putCommentAction(Request $request, PublicationComment $comment) {
        $this->authorize($comment->getOwner());
        $this->authorizePublication($comment->getPublication());

        $form = $this->createForm(new PublicationCommentFormType(), $comment, array('method' => 'PUT'));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $comment->setContent(SecurityUtilities::sanitize($comment->getContent()));

            $em = $this->getDoctrine()->getManager();

            $em->persist($comment);
            $em->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        return $this->view($form->getErrors(), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * Get the publication comments, orderer by creation date
     *
     * GET /v2/publication/{publication_id}/comments?limit=20&loaded=5
     *
     * limit:   Number of comments to return
     * loaded:  Number of comments already loaded
     *
     * @param Request $request
     * @param \NaturaPass\PublicationBundle\Entity\Publication $publication
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("publication", class="NaturaPassPublicationBundle:Publication")
     */
    public function getCommentsAction(Request $request, Publication $publication) {
        $limit = $request->query->get('limit', 20);
        $loaded = $request->query->get('loaded', 0);

        $total = count($publication->getComments());

        if (($total - $loaded) < $limit) {
            $limit = $total - $loaded;
        }

        $offset = $loaded;

        $repo = $this->getDoctrine()->getManager()->getRepository('NaturaPassPublicationBundle:PublicationComment');

        $comments = PublicationSerialization::serializePublicationComments(
            $repo->findBy(array('publication' => $publication), array('created' => 'DESC'), $limit, $offset),
            $this->getUser()
        );

        return $this->view(array(
            'comments' => $comments,
            'loaded' => count($comments)), Codes::HTTP_OK);
    }
}
