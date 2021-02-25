<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 10/09/14
 * Time: 12:21
 */

namespace Api\ApiBundle\Tests\Controller\v1;


use Api\ApiBundle\Tests\ApiClientTest;
use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;
use NaturaPass\LoungeBundle\Entity\Lounge;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class LoungesControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\LoungesController
 */
class LoungesControllerTest extends ApiControllerTest {

    protected static $lounge;

    /**
     * @covers \Api\ApiBundle\Controller\v1\LoungesController::postLoungeAction()
     */
    public function testPostLoungeAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_lounge'),
            array(),
            array('lounge' =>
                array('photo' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/image1.jpg', 'image1.jpg', 'image/jpeg')
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'lounge' => array(
                        'name' => 'Salon PHPUnit',
                        'description' => 'Description Salon PHPUnit1',
                        'geolocation' => 1,
                        'access' => Lounge::ACCESS_PROTECTED,
                        'meetingAddress' => array(
                            'address' => "Impasse du Jura, 01800 CHARNOZ SUR AIN, France",
                            'latitude' => 45.5587,
                            'longitude' => 7.566,
                        ),
                        'meetingDate' => '18/09/2014 06:00',
                        'endDate' => '20/10/2014 06:00',
                    )
                )
            ));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('lounge_id', $content);

        static::$lounge = $content['lounge_id'];
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\LoungesController::postLoungeAction()
     */
    public function testPutLoungeAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_lounge', array('lounge' => static::$lounge)),
            array(),
            array('lounge' =>
                array('photo' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/image1.jpg', 'image1.jpg', 'image/jpeg')
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'lounge' => array(
                        'name' => 'Salon PHPUnit',
                        'description' => 'Description Salon PHPUnit1 Editée',
                        'geolocation' => 1,
                        'access' => Lounge::ACCESS_PROTECTED,
                        'meetingAddress' => array(
                            'address' => "Impasse du Jura, 01800 CHARNOZ SUR AIN, France",
                            'latitude' => 45.5587,
                            'longitude' => 7.566,
                        ),
                        'meetingDate' => '18/09/2014 06:00',
                        'endDate' => '20/10/2014 06:00',
                    )
                )
            ));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('lounge', $content);

        static::$lounge = $content['lounge'];
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\LoungesController::postLoungeMessageAction()
     */
    public function testPostLoungeMessageAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_lounge_message', array('lounge' => static::$lounge['id'])),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array('content' => 'Ceci est un commentaire de PHPUnit')
            ));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('message', $content);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\LoungesController::postLoungeMessageAction()
     */
    public function testGetLoungeMessagesAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_lounge_messages', array('lounge' => static::$lounge['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('messages', $content);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\LoungesController::postLoungeInviteUserAction()
     */
    public function testPostLoungeInviteUserAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_lounge_invite_user', array('lounge' => static::$lounge['id'], 'receiver' => $this->clients->get('Nicolas')->getUser()->getId())));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\LoungesController::putLoungeUserJoinAction()
     */
    public function testPutLoungeUserJoinAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('PUT', $this->router->generate('api_v1_put_lounge_user_join', array('lounge' => static::$lounge['id'], 'user' => $this->clients->get('Nicolas')->getUser()->getId())));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\LoungesController::getLoungeSubscribersAction()
     */
    public function testGetLoungeSubscribersAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_lounge_subscribers', array('lounge' => static::$lounge['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('subscribers', $content);
    }
} 