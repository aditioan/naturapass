<?php

namespace Api\ApiBundle\Controller\v1;

use Doctrine\Common\Collections\ArrayCollection;
use NaturaPass\GraphBundle\Entity\Recommendation;
use NaturaPass\GraphBundle\Entity\Pertinence;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Description of GraphsController
 *
 * @author nicolasmendez
 */
class GraphsController extends ApiRestController
{

    /**
     * GET /graph/pertinences
     *
     * @View(serializerGroups={"PertinenceDetail"})
     */
    public function getGraphPertinencesAction()
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $pertinences = $this->getDoctrine()->getRepository('NaturaPassGraphBundle:Pertinence')->findAll();

        return $this->view(array('pertinences' => $pertinences), Codes::HTTP_OK);
    }

    /**
     * PUT /graphs/{pertinence}/pertinences/{value}
     *
     * @param Request $request
     * @param Pertinence $pertinence
     *
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("pertinence", class="NaturaPassGraphBundle:Pertinence", options={"mapping": {"pertinence": "type"}})
     *
     * @throws HttpException
     */
    public function putGraphPertinenceAction(Request $request, Pertinence $pertinence)
    {
        $this->authorize(null, 'ROLE_ADMIN');

        $value = $request->request->get('pertinence[value]', $pertinence->getValue(), true);
        $loss = $request->request->get('pertinence[loss]', $pertinence->getLoss(), true);

        if ($value >= 0.00 && $value <= 10.00 && $loss >= 0.00 && $loss <= 1.00) {
            $pertinence->setValue($value);
            $pertinence->setLoss($loss);

            $this->getDoctrine()->getManager()->persist($pertinence);
            $this->getDoctrine()->getManager()->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * FR : Calcul les recommandations d'un graph
     * EN : Calculating the recommendations of a graph
     *
     * GET /graph/friends/recommendations?limit=3
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getGraphFriendsRecommendationsAction(Request $request)
    {
        $this->authorize();

        $manager = $this->getDoctrine()->getManager();

        $recommendations = $manager->getRepository('NaturaPassGraphBundle:Recommendation')->getByOwner($this->getUser(), $request->query->get('offset', 0), $request->query->get('limit', 10));

        if (count($recommendations) === 0) {
            $recommendations = $this->getGraphService()->generateUserRecommendations($this->getUser());
            $recommendations = $manager->getRepository('NaturaPassGraphBundle:Recommendation')->getByOwner($this->getUser(), $request->query->get('offset', 0), $request->query->get('limit', 10));
        }

        $targets = new ArrayCollection();
        $diff = 0;
        foreach ($recommendations as $recommendation) {
            if ($recommendation->getPertinence() > 0) {
                $diff = (new \DateTime())->diff($recommendation->getUpdated());

                if ($recommendation->getAction() !== Recommendation::ACTION_REMOVED || ($recommendation->getAction() === Recommendation::ACTION_REMOVED && $diff->d > 7)) {
                    $formatted = $this->getFormatUser($recommendation->getTarget(), true);
                    $formatted['pertinence'] = $recommendation->getPertinence();

                    $targets->add($formatted);
                }
            }
        }

        return $this->view(array('recommendations' => $targets, 'diff' => $diff), Codes::HTTP_OK);
    }

    /**
     * FR : Mets à jour une recommendation à la lecture de l'utilisateur
     * EN : Upgrade a recommendation at the reading of the user
     *
     * PUT /{user}/recommendation/viewed
     *
     * @var User $user
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     *
     * @throws HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putGraphRecommendationViewedAction(User $user)
    {
        $this->authorize();

        $recommendation = $this->getDoctrine()->getManager()->getRepository('NaturaPassGraphBundle:Recommendation')->findOneBy(array(
            'owner' => $this->getUser(),
            'target' => $user
        ));

        if (is_object($recommendation)) {
            $pertinence = $recommendation->getPertinence();

            $recommendation->setAction(Recommendation::ACTION_VIEWED)
                ->setPertinence(($pertinence - $recommendation->getAction() ^ 2 >= 0) ? $pertinence - $recommendation->getAction() ^ 2 : 0);

            $manager = $this->getDoctrine()->getManager();

            $manager->persist($recommendation);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.recommendation.nonexistent'));
    }

    /**
     * FR : Mets à jour une recommendation à sa suppression par l'utilisateur
     * EN : Upgrade a recommendation for deletion by the user
     *
     * PUT /{user}/recommendation/removed
     *
     * @var User $user
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     *
     * @throws HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putGraphRecommendationRemovedAction(User $user)
    {
        $this->authorize();

        $recommendation = $this->getDoctrine()->getManager()->getRepository('NaturaPassGraphBundle:Recommendation')->findOneBy(array(
            'owner' => $this->getUser(),
            'target' => $user
        ));

        if (is_object($recommendation)) {
            $pertinence = $recommendation->getPertinence();

            $recommendation->setAction(Recommendation::ACTION_REMOVED)
                ->setPertinence(($pertinence - $recommendation->getAction() ^ 2 >= 0) ? $pertinence - $recommendation->getAction() ^ 2 : 0);

            $manager = $this->getDoctrine()->getManager();

            $manager->persist($recommendation);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.recommendation.nonexistent'));
    }

    /**
     * FR : Mets à jour une recommendation à son utilisation par l'utilisateur
     * EN : Upgrade a recommendation for its use by the user
     *
     * PUT /{user}/recommendation/used
     *
     * @var User $user
     * @ParamConverter("user", class="NaturaPassUserBundle:User")
     *
     * @throws HttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putGraphRecommendationUsedAction(User $user)
    {
        $this->authorize();

        $recommendation = $this->getDoctrine()->getManager()->getRepository('NaturaPassGraphBundle:Recommendation')->findOneBy(array(
            'owner' => $this->getUser(),
            'target' => $user
        ));

        if (is_object($recommendation)) {
            $recommendation->setAction(Recommendation::ACTION_USED);

            $manager = $this->getDoctrine()->getManager();

            $manager->persist($recommendation);
            $manager->flush();

            return $this->view($this->success(), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.user.recommendation.nonexistent'));
    }

}
