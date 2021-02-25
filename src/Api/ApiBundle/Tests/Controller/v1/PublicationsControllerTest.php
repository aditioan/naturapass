<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/08/14
 * Time: 11:51
 */

namespace Api\ApiBundle\Tests\Controller\v1;

use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use NaturaPass\PublicationBundle\Entity\PublicationAction;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class PublicationsControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\PublicationsController
 */
class PublicationsControllerTest extends ApiControllerTest {

    /**
     * @var array
     */
    protected static $publication;

    /**
     * @var array
     */
    protected static $comment;

    public function setUp() {
        parent::setUp();

        $this->logClientIn($this->clients->get('Vincent'));
    }

    /**
     * Ajoute une publication avec photo
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationAction()
     */
    public function testPostPublication() {
        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_publication'),
            array(),
            array('publication' =>
                array('media' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/image1.jpg', 'image1.jpg', 'image/jpeg')
                ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'publication' => array(
                        'content' => "Publication d'une photo",
                        'sharing' => array(
                            'share' => Sharing::NATURAPASS
                        ),
                        'geolocation' => array(
                            'latitude' => 46.1801455,
                            'longitude' => 5.318104,
                            'address' => 'Avenue du Revermont, 01250 Ceyzériat'
                        ),
                        'date' => (new \DateTime())->format(\DateTime::ATOM)
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('publication', $content);

        self::$publication = $content['publication'];
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationGeolocationAction()
     */
    public function testPutPublicationGeolocation() {
        $this->clients->get('Vincent')->request('PUT',
            $this->router->generate('api_v1_put_publication_geolocation', array('publication' => self::$publication['id'])),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array('geolocation' => array(
                'latitude' => 46.1801455,
                'longitude' => 5.318104,
                'address' => 'Avenue du Revermont, 01250 Ceyzériat'
            )))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationLocalityAction()
     */
    public function testGetPublicationLocality() {
        $this->clients->get('Vincent')->request('GET',
            $this->router->generate('api_v1_get_publication_locality', array('publication' => self::$publication['id']))
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('locality', $content);
        $this->assertInternalType('array', $content['locality']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationRotateAction()
     */
    public function testPutPublicationRotate() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_publication_rotate', array('publication' => self::$publication['id'], 'degree' => 180)));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('media', $content);
        $this->assertInternalType('array', $content['media']);
    }

    /**
     * Like sur une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationLikeAction()
     */
    public function testPutPublicationLike() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_publication_like', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertEquals(1, $content['likes']);
        $this->assertEquals(0, $content['unlikes']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationActionsAction()
     */
    public function testGetPublicationActionsAction() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication_actions', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertCount(1, $content['likes']);
        $this->assertCount(0, $content['unlikes']);
    }


    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationUsersActionAction()
     */
    public function testGetPublicationUsersActionAction() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication_users_action', array('publication' => self::$publication['id'], 'state' => PublicationAction::STATE_LIKE)));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('users', $content);
        $this->assertCount(1, $content['users']);
    }

    /**
     * Unlike sur une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationUnlikeAction()
     */
    public function testPutPublicationUnlike() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_publication_unlike', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertEquals(0, $content['likes']);
        $this->assertEquals(1, $content['unlikes']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationActionsAction()
     */
    public function testGetPublicationActionsAction2() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication_actions', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertCount(0, $content['likes']);
        $this->assertCount(1, $content['unlikes']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationUsersActionAction()
     */
    public function testGetPublicationUsersActionAction2() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication_users_action', array('publication' => self::$publication['id'], 'state' => PublicationAction::STATE_UNLIKE)));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('users', $content);
        $this->assertCount(1, $content['users']);
    }

    /**
     * Récupérer actions d'une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationsAction()
     */
    public function testGetPublicationActions() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication_actions', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertCount(0, $content['likes']);
        $this->assertCount(1, $content['unlikes']);
    }

    /**
     * Ajout de commentaire
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationCommentAction()
     */
    public function testPostPublicationComment() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('POST', $this->router->generate('api_v1_post_publication_comment', array('publication' => self::$publication['id'])),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array('content' => "Commentaire 1"))
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('comment', $content);

        self::$comment = $content['comment']['id'];
    }

    /**
     * Modification de commentaire
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationCommentAction()
     */
    public function testPutPublicationComment() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('PUT', $this->router->generate('api_v1_put_publication_comment', array('comment' => self::$comment)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array('content' => "Commentaire 1 modifié"))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
    }

    /**
     * Récupérations des commentaires
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationCommentsAction()
     */
    public function testGetPublicationComments() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication_comments', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('comments', $content);

        $this->assertCount(1, $content['comments']);
    }

    /**
     * Unlike sur une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationCommentUnlikeAction()
     */
    public function testPutPublicationCommentUnlike() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_publication_comment_unlike', array('comment' => self::$comment)));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertEquals(0, $content['likes']);
        $this->assertEquals(1, $content['unlikes']);
    }

    /**
     * Like sur une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationCommentLikeAction()
     */
    public function testPutPublicationCommentLike() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_publication_comment_like', array('comment' => self::$comment)));

        $this->assertEquals(Codes::HTTP_OK, $this->clients->get('Vincent')->getResponse()->getStatusCode(), $this->getErrorMessage($this->clients->get('Vincent')->getResponse()));

        $content = $this->decodeResponseContent($this->clients->get('Vincent')->getResponse());
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertEquals(1, $content['likes']);
        $this->assertEquals(0, $content['unlikes']);
    }


    /**
     * Edition d'une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationAction()
     */
    public function testPutPublication() {
        $date = new \DateTime();

        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_publication', array('publication' => self::$publication['id'])),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'publication' => array(
                        'content' => "Publication d'une photo, édité",
                        'sharing' => array(
                            'share' => Sharing::USER
                        ),
                        'geolocation' => array(
                            'latitude' => 44.55,
                            'longitude' => 5.789,
                            'address' => 'Autre adresse'
                        ),
                        'date' => $date->format(\DateTime::ATOM)
                    )
                )
            )
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * Récupérer une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationAction()
     */
    public function testGetPublication() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('publication', $content);
        $this->assertArrayHasKey('geolocation', $content['publication']);

        $this->assertEquals("Publication d'une photo, édité", $content['publication']['content']);
        $this->assertEquals(Sharing::USER, $content['publication']['sharing']['share']);
        $this->assertEquals('44.55', $content['publication']['geolocation']['latitude']);
        $this->assertEquals('5.789', $content['publication']['geolocation']['longitude']);
        $this->assertEquals('Autre adresse', $content['publication']['geolocation']['address']);
    }


    /**
     * Edition d'une publication avec suppression de la géolocalisation
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::putPublicationAction()
     */
    public function testPutPublication2() {
        $date = new \DateTime();

        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_publication', array('publication' => self::$publication['id'])),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'publication' => array(
                        'content' => "Publication d'une photo, édité une deuxième fois",
                        'sharing' => array(
                            'share' => Sharing::NATURAPASS
                        ),
                        'date' => $date->format(\DateTime::ATOM)
                    )
                )
            )
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * Récupérer une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationAction()
     */
    public function testGetPublication2() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publication', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('publication', $content);
        $this->assertArrayNotHasKey('geolocation', $content['publication']);

        $this->assertEquals("Publication d'une photo, édité une deuxième fois", $content['publication']['content']);
        $this->assertEquals(Sharing::NATURAPASS, $content['publication']['sharing']['share']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationsUserAction()
     */
    public function testGetPublicationsUserAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_publications_user', array('user' => $this->clients->get('Vincent')->getUser()->getId())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('publications', $content);
        $this->assertCount(1, $content['publications']);
    }


    /**
     * Suppression d'un like/unlike sur une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::deletePublicationActionAction()
     */
    public function testDeletePublicationAction() {
        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_publication_action', array('publication' => self::$publication['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertEquals(0, $content['likes']);
        $this->assertEquals(0, $content['unlikes']);
    }

    /**
     * Suppression d'un like/unlike sur un commentaire
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::deletePublicationCommentActionAction()
     */
    public function testDeletePublicationCommentAction() {
        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_publication_comment_action', array('comment' => self::$comment)));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('likes', $content);
        $this->assertArrayHasKey('unlikes', $content);

        $this->assertEquals(0, $content['likes']);
        $this->assertEquals(0, $content['unlikes']);
    }


    /**
     * Signalement d'une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationSignalAction()
     */
    public function testPostPublicationSignal() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('POST', $this->router->generate('api_v1_post_publication_signal', array('publication' => self::$publication['id'])),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'publication' => array(
                        'explanation' => "Signalement d'une publication"
                    )
                )
            )
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));

        $this->logClientIn($this->clients->get('Vincent'));
    }

    /**
     *  Suppression d'un commentaire
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::deletePublicationCommentAction()
     */
    public function testDeletePublicationComment() {
        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_publication_comment', array('comment' => self::$comment)));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        self::$comment = null;
    }
} 