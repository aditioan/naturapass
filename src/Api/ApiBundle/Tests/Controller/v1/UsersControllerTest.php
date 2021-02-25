<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 05/09/14
 * Time: 09:29
 */

namespace Api\ApiBundle\Tests\Controller\v1;


use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class UsersControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\UsersController
 */
class UsersControllerTest extends ApiControllerTest {
    /**
     * @var array
     */
    protected static $address;

    public function setUp() {
        parent::setUp();
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserAction()
     */
    public function testPostUser1() {
        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_user'),
            array(),
            array('user' =>
                array('photo' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/image1.jpg', 'image1.jpg', 'image/jpeg')
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'user' => array(
                        'courtesy' => User::COURTESY_MISTER,
                        'lastname' => 'PHPUnit1',
                        'firstname' => 'PHPUnit1',
                        'email' => 'phpunit' . $this->clients->get('Vincent')->getId() . '@naturapass.com',
                        'password' => '22e0366ffd02fdd942a960119e71fce654c2ccf5'
                    ),
                    'device' => array(
                        'type' => 'ios',
                        'identifier' => 'TESTPHPUNIT1',
                        'name' => 'Apple iPhone',
                        'authorized' => 1
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('user_id', $content);

        $this->clients->get('Vincent')->setUser(self::$kernel->getContainer()->get('doctrine')->getManager()->getRepository('NaturaPassUserBundle:User')->findOneById($content['user_id']));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserAction()
     */
    public function testPostUser2() {
        $this->clients->get('Nicolas')->request('POST', $this->router->generate('api_v1_post_user'),
            array(),
            array('user' =>
                array('photo' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/image1.jpg', 'image1.jpg', 'image/jpeg')
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'user' => array(
                        'courtesy' => User::COURTESY_MISTER,
                        'lastname' => 'PHPUnit2',
                        'firstname' => 'PHPUnit2',
                        'email' => 'phpunit' . $this->clients->get('Nicolas')->getId() . '@naturapass.com',
                        'password' => '22e0366ffd02fdd942a960119e71fce654c2ccf5'
                    ),
                    'device' => array(
                        'type' => 'ios',
                        'identifier' => 'TESTPHPUNIT2',
                        'name' => 'Apple iPhone',
                        'authorized' => 1
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('user_id', $content);

        $this->clients->get('Nicolas')->setUser(self::$kernel->getContainer()->get('doctrine')->getManager()->getRepository('NaturaPassUserBundle:User')->findOneById($content['user_id']));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserAction()
     */
    public function testPostUser3() {
        $this->clients->get('Sylvain')->request('POST', $this->router->generate('api_v1_post_user'),
            array(),
            array('user' =>
                array('photo' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/image1.jpg', 'image1.jpg', 'image/jpeg')
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'user' => array(
                        'courtesy' => User::COURTESY_MISTER,
                        'lastname' => 'PHPUnit3',
                        'firstname' => 'PHPUnit3',
                        'email' => 'phpunit' . $this->clients->get('Sylvain')->getId() . '@naturapass.com',
                        'password' => '22e0366ffd02fdd942a960119e71fce654c2ccf5'
                    ),
                    'device' => array(
                        'type' => 'ios',
                        'identifier' => 'TESTPHPUNIT3',
                        'name' => 'Apple iPhone',
                        'authorized' => 1
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Sylvain'));
        $this->assertArrayHasKey('user_id', $content);

        $this->clients->get('Sylvain')->setUser(self::$kernel->getContainer()->get('doctrine')->getManager()->getRepository('NaturaPassUserBundle:User')->findOneById($content['user_id']));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::putUsersAction()
     * @skippedTest
     */
    public function testPutUsers() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_users'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'user' => array(
                        'courtesy' => User::COURTESY_MISTER,
                        'lastname' => 'PHPUnit1.1',
                        'firstname' => 'PHPUnit1.1',
                        'email' => 'phpunit' . $this->clients->get('Vincent')->getId() . '@naturapass.com'
                    )
                )
            )
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserProfilePictureAction()
     */
    public function testPostUserProfilePictureAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_user_profile_picture'),
            array(),
            array('user' =>
                array(
                    'photo' => new UploadedFile($this->getRootDir() . 'web/tests/image2.jpg', 'image2.jpg', 'image/jpeg')
                )
            )
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }


    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserAddressAction()
     */
    public function testPostUserAddress() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_user_address'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'address' => array(
                        'title' => 'Une adresse de PHPUnit1',
                        'latitude' => 43.3232,
                        'longitude' => 4.3392,
                        'address' => "Adresse de test",
                        'altitude' => 72.4,
                        'favorite' => true
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('address', $content);

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_user_address'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'address' => array(
                        'title' => 'Une adresse de PHPUnit1',
                        'latitude' => 43.3232,
                        'longitude' => 4.3392,
                        'address' => "Adresse qui va être mise en favorite",
                        'altitude' => 72.4,
                        'favorite' => false
                    )
                )
            )
        );


        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('address', $content);

        self::$address = $content['address'];
    }


    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::putUserAddressAction()
     */
    public function testPutUserAddress() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_user_address', array('address' => self::$address['id'])),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'address' => array(
                        'title' => 'Une adresse de PHPUnit1',
                        'latitude' => 43.3232,
                        'longitude' => 4.3392,
                        'address' => "Adresse favorite",
                        'altitude' => 72.4,
                        'favorite' => false
                    )
                )
            )
        );

        $this->assertStatusCodeEquals(Codes::HTTP_NO_CONTENT, $this->clients->get('Vincent'));
    }


    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::putUserAddressFavoriteAction()
     */
    public function testPutUserAddressFavorite() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_user_address_favorite', array('address' => self::$address['id'], 'favorite' => 1)));

        $this->assertStatusCodeEquals(Codes::HTTP_NO_CONTENT, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::getUserAddressesAction()
     */
    public function testGetUserAddresses() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_user_addresses', array('favorite' => true)));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('address', $content);


        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_user_addresses'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('addresses', $content);
        $this->assertEquals(2, count($content['addresses']));
    }


    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::deleteUserAddressAction()
     */
    public function testDeleteUserAddressAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_user_address', array('address' => self::$address['id'])));

        $this->assertStatusCodeEquals(Codes::HTTP_NO_CONTENT, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::getUserLoginAction()
     */
    public function testGetUserLogin() {
        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_user_login', array('email' => 'phpunit' . $this->clients->get('Nicolas')->getId() . '@naturapass.com', 'password' => '22e0366ffd02fdd942a960119e71fce654c2ccf5', 'device' => 'ios', 'identifier' => 'TESTPHPUNIT2')));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::getUserConnectedAction()
     */
    public function testGetUserConnected() {
        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_user_login', array('email' => 'phpunit' . $this->clients->get('Nicolas')->getId() . '@naturapass.com', 'password' => '22e0366ffd02fdd942a960119e71fce654c2ccf5', 'device' => 'ios', 'identifier' => 'TESTPHPUNIT2')));
        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_user_connected'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('user', $content);
        $this->assertArrayHasKey('id', $content['user']);
        $this->assertEquals($this->clients->get('Nicolas')->getUser()->getId(), $content['user']['id']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::getUserDevicesAction()
     */
    public function testGetUserDevices() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_user_devices'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('devices', $content);
        $this->assertEquals(1, count($content['devices']));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::deleteUserDeviceAction()
     */
    public function testDeleteUserDevice() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_user_device', array('type' => 'ios', 'identifier' => 'TESTPHPUNIT1')));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::getUserProfileAction()
     */
    public function testGetUserProfile() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_user_profile', array('user' => $this->clients->get('Vincent')->getUser()->getUsertag())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('profile', $content);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserGeolocationAction()
     */
    public function testPostUserGeolocation() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_user_geolocation'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'geolocation' => array(
                        'latitude' => 45.75,
                        'longitude' => 4.85,
                        'altitude' => 230,
                        'address' => "Adresse au format Google"
                    )
                )
            )
        );

        $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserFriendshipAskAction()
     */
    public function testPostUserFriendshipAskAction1() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_user_friendship_ask', array('receiver' => $this->clients->get('Nicolas')->getUser()->getId())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('friendship', $content);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::putUserFriendshipConfirmAction()
     */
    public function testPutUserFriendshipConfirmAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('PUT', $this->router->generate('api_v1_put_user_friendship_confirm', array('receiver' => $this->clients->get('Vincent')->getUser()->getId())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));

        $this->assertArrayHasKey('friendship', $content);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::deleteUserFriendshipAction()
     */
    public function testDeleteUserFriendshipAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('DELETE', $this->router->generate('api_v1_delete_user_friendship', array('receiver' => $this->clients->get('Vincent')->getUser()->getId())));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));

    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::postUserFriendshipAskAction()
     */
    public function testPostUserFriendshipAskAction2() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_user_friendship_ask', array('receiver' => $this->clients->get('Nicolas')->getUser()->getId())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('friendship', $content);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::putUserFriendshipRejectAction()
     */
    public function testPutUserFriendshipRejectAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('PUT', $this->router->generate('api_v1_put_user_friendship_reject', array('receiver' => $this->clients->get('Vincent')->getUser()->getId())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));

        $this->assertArrayHasKey('friendship', $content);
    }
} 