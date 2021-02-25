<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/08/14
 * Time: 11:51
 */

namespace Api\ApiBundle\Tests\Controller\v1;

use Api\ApiBundle\Tests\ApiClientTest;
use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class MessagesControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\MessagesController
 */
class MessagesControllerTest extends ApiControllerTest {

    /**
     * @var array
     */
    protected static $message;

    public function setUp() {
        parent::setUp();

        $this->logClientIn($this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::postMessagesAction()
     */
    public function testPostMessage() {
        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_messages'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'message' => array(
                        'content' => "Publication d'une photo",
                        'pendingParticipants' => array(array('id' => $this->clients->get('Nicolas')->getUser()->getId()))
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('message', $content);

        self::$message = $content['message'];
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::getUserConversationsAction()
     */
    public function testGetUserConversationsAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_user_conversations'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));

        $this->assertArrayHasKey('messages', $content);
        $this->assertEquals(1, count($content['messages']));

        $this->logClientIn($this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::postMessagesAction()
     */
    public function testPostMessage2() {
        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_messages'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'message' => array(
                        'conversationId' => self::$message['conversation']['id'],
                        'content' => "Publication d'une photo",
                        'participants' => array(
                            array(
                                'id' => $this->clients->get('Nicolas')->getUser()->getId(),
                                'usertag' => $this->clients->get('Nicolas')->getUser()->getUsertag()
                            )
                        )
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('message', $content);

        self::$message = $content['message'];
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::getUserMessagesAction()
     */
    public function testGetUserMessagesAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_user_messages'), array('conversationId' => self::$message['conversation']['id']));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));

        $this->assertArrayHasKey('messages', $content);
        $this->assertEquals(2, count($content['messages']));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::getConversationMessagesAction()
     */
    public function testGetConversationMessagesAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('GET', $this->router->generate('api_v1_get_conversation_messages'), array('conversationId' => self::$message['conversation']['id'], 'reverse' => 1));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));

        $this->assertArrayHasKey('messages', $content);
        $this->assertEquals(2, count($content['messages']));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::postMessagesAction()
     */
    public function testPostMessage3() {
        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_messages'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'message' => array(
                        'conversationId' => self::$message['conversation']['id'],
                        'content' => "Publication d'une photo",
                        'participants' => array(
                            array(
                                'id' => $this->clients->get('Nicolas')->getUser()->getId(),
                                'usertag' => $this->clients->get('Nicolas')->getUser()->getUsertag()
                            )
                        )
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
        $this->assertArrayHasKey('message', $content);

        self::$message = $content['message'];
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::putReadMessageAction()
     */
    public function testPutReadMessageAction() {
        /**
         * @var $client ApiClientTest
         */
        $client = $this->clients->get('Nicolas');

        $this->logClientIn($client);
        $client->request('PUT', $this->router->generate('api_v1_put_read_message',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'conversation' => array(
                    'id' => self::$message['conversation']['id']
                )
            ))
        ));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $client);

        $this->assertArrayHasKey('readCount', $content);
        $this->assertGreaterThan(0, $content['readCount']);

        $this->logClientIn($this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::postMessagesAction()
     */
    public function testPostMessage4() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Nicolas')->request('POST', $this->router->generate('api_v1_post_messages'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'message' => array(
                        'conversationId' => self::$message['conversation']['id'],
                        'content' => "Publication d'une photo",
                        'participants' => array(
                            array(
                                'id' => $this->clients->get('Vincent')->getUser()->getId(),
                                'usertag' => $this->clients->get('Vincent')->getUser()->getUsertag()
                            )
                        )
                    )
                )
            )
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Nicolas'));
        $this->assertArrayHasKey('message', $content);

        self::$message = $content['message'];

        $this->logClientIn($this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::postConversationParticipantsAction()
     */
    public function testPostConversationParticipantsAction() {
        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_conversation_participants'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'conversation' => array(
                    'id' => self::$message['conversation']['id'],
                    'participants' => array(array('id' => $this->clients->get('Sylvain')->getId()))
                )
            ))
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('participants', $content);
    }

    /**
     * Récupérations des commentaires
     *
     * @covers \Api\ApiBundle\Controller\v1\MessagesController::getChatConversationAction()
     */
    public function testGetChatConversationAction() {
        $this->clients->get('Vincent')->request('GET', $this->router->generate('api_v1_get_chat_conversation'));

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('conversation', $content);
    }
} 