<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 03/09/14
 * Time: 15:58
 */

namespace Api\ApiBundle\Tests\Controller\v1;


use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;
use NaturaPass\MainBundle\Entity\Sharing;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class GroupsControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\GroupsController
 */
class GroupsControllerTest extends ApiControllerTest {

    protected static $group;

    public function setUp() {
        parent::setUp();

        $this->logClientIn($this->clients->get('Vincent'));
    }

    public function testPostGroup() {
        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_group'),
            array(),
            array('group' =>
                array('photo' =>
                    array(
                        'file' => new File($this->getRootDir() . 'web/tests/image1.jpg', 'image1.jpg', 'image/jpeg')
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'group' => array(
                        'name' => "PHPUnit Group",
                        'description' => 'Description du groupe PHPUnit',
                        'access' => Group::ACCESS_SEMIPROTECTED
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('group_id', $content);

        self::$group = array(
            'id' => $content['group_id'],
            'name' => "PHPUnit Group",
            'description' => 'Description du groupe PHPUnit',
            'access' => Group::ACCESS_PROTECTED
        );
    }

    public function testGetGroupsOwning() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_groups_owning'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('groups', $content);
        $this->assertGreaterThanOrEqual(1, count($content['groups']));
    }

    /**
     * Client 2 rejoint le groupe
     */
    public function testPostGroupJoin() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('POST', $this->router->generate('api_v1_post_group_join', array('user' => $this->clients->get('Nicolas')->getUser()->getId(), 'group' => self::$group['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('access', $content);
        $this->assertEquals(GroupUser::ACCESS_RESTRICTED, $content['access']);
    }

    public function testGetGroupsPending() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_groups_pending'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('groups', $content);
        $this->assertCount(1, $content['groups']);
    }

    /**
     * Retour des demandes d'accès à un groupe
     */
    public function testGetGroupAsks() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_group_asks', array('group' => self::$group['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('subscribers', $content);
        $this->assertCount(1, $content['subscribers']);
    }

    public function testGetGroups() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_groups'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('groups', $content);
        $this->assertGreaterThanOrEqual(1, count($content['groups']));
    }

    public function testPutGroupUserJoin() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_group_user_join', array('group' => self::$group['id'], 'user' => $this->clients->get('Nicolas')->getUser()->getId())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('subscriber', $content);
    }

    public function testGetGroupSubscribers() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_group_subscribers', array('group' => self::$group['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('subscribers', $content);
        $this->assertGreaterThanOrEqual(1, count($content['subscribers']));
    }

    public function testPostGroupPublication() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('POST', $this->router->generate('api_v1_post_publication'),
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
                        'groups' => array(self::$group['id']),
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

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('publication', $content);
    }

    public function testGetGroupPublications() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_group_publications', array('group' => self::$group['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('publications', $content);
        $this->assertGreaterThanOrEqual(1, count($content['publications']));
    }

    public function testPutGroupSubscriberAdmin() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_group_subscriber_admin', array('group' => self::$group['id'], 'subscriber' => $this->clients->get('Nicolas')->getUser()->getId())));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('isAdmin', $content);

        $this->assertTrue($content['isAdmin']);
    }

    public function testPutGroupSubscriberMailable() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_group_subscriber_mailable', array('group' => self::$group['id'], 'mailable' => 1)));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    public function testDeleteGroupJoin() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('DELETE', $this->router->generate('api_v1_delete_group_join', array('group' => self::$group['id'], 'user' => $this->clients->get('Nicolas')->getUser()->getId())));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
    }

    public function testPutGroup() {
        $this->clients->get('Vincent')->request('PUT', $this->router->generate('api_v1_put_group', array('group' => self::$group['id'])),
            array(),
            array('group' =>
                array('photo' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/image2.jpg', 'image2.jpg', 'image/jpeg')
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'group' => array(
                        'name' => "PHPUnit Group",
                        'description' => 'Description du groupe PHPUnit changée',
                        'access' => Group::ACCESS_PUBLIC
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('group', $content);

        $this->assertEquals(Group::ACCESS_PUBLIC, $content['group']['access']);
        $this->assertEquals('Description du groupe PHPUnit changée', $content['group']['description']);
    }

    public function testGetGroup() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_group', array('group' => self::$group['id'])));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('group', $content);
    }


    /**
     * Suppression d'un groupe
     *
     * @covers \Api\ApiBundle\Controller\v1\GroupsController::deleteGroupAction()
     */
    public function testDeleteGroup() {
        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_group', array('group' => self::$group['id'])));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }
} 