<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/06/15
 * Time: 10:17
 */

namespace Api\ApiBundle\Tests\Controller\Admin;


use Admin\SentinelleBundle\Entity\CardLabel;
use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;

/**
 * Class ObservationsControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\ObservationsController
 */
class CardsControllerTest extends ApiControllerTest {

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CardsController::postCardAction()
     */
    public function testPostCardAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'POST',
            $this->router->generate('api_admin_post_card'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'card' => array(
                    'name' => 'Card 1',
                    'visible' => 1,
                    'labels' => array(
                        array(
                            'name' => 'Label 1 string of Card 1',
                            'visible' => 1,
                            'type' => CardLabel::TYPE_STRING
                        )
                    )
                )
            ))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CardsController::putCardAction()
     */
    public function testPutCardAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'PUT',
            $this->router->generate('api_admin_put_card', array('card' => 1)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'card' => array(
                    'name' => 'Card 1',
                    'visible' => 1,
                    'labels' => array(
                        array(
                            'name' => 'Label 1 (edited) string of Card 1',
                            'visible' => 1,
                            'type' => CardLabel::TYPE_STRING
                        ),
                        array(
                            'name' => 'Label 2 integer of Card 1',
                            'visible' => 1,
                            'type' => CardLabel::TYPE_INT
                        )
                    )
                )
            ))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CardsController::getCardAction()
     */
    public function testGetCardAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'GET',
            $this->router->generate('api_admin_get_card', array('card' => 1))
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('card', $content);
        $this->assertCount(2, $content['card']['labels']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CardsController::getCardsAction()
     */
    public function testGetCardsAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'GET',
            $this->router->generate('api_admin_get_cards'),
            array('filter' => 'Card 1')
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('cards', $content);
        $this->assertCount(1, $content['cards']);
    }
}