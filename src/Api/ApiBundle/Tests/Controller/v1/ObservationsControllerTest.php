<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/06/15
 * Time: 10:17
 */

namespace Api\ApiBundle\Tests\Controller\v1;


use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;

/**
 * Class ObservationsControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\ObservationsController
 */
class ObservationsControllerTest extends ApiControllerTest {

    /**
     * @covers \Api\ApiBundle\Controller\v1\ObservationsController::postObservationAction()
     */
    public function testPostObservationAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'POST',
            $this->router->generate('api_v1_post_observation', array('publication' => 1)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'observation' => array(
                    'category' => 2,
                    'attachments' => array(
                        array(
                            'label' => 1,
                            'value' => 'This a value'
                        ),
                        array(
                            'label' => 2,
                            'value' => 25
                        )
                    )
                )
            ))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\ObservationsController::putObservationAction()
     */
    public function testPutObservationAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'PUT',
            $this->router->generate('api_v1_put_observation', array('observation' => 1)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'observation' => array(
                    'category' => 2,
                    'attachments' => array(
                        array(
                            'label' => 1,
                            'value' => 'This a value edited'
                        ),
                        array(
                            'label' => 2,
                            'value' => 30
                        )
                    )
                )
            ))
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('observation', $content);
        $this->assertArrayHasKey('attachments', $content['observation']);
        $this->assertCount(2, $content['observation']['attachments']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\ObservationsController::getObservationAction()
     */
    public function testGetObservationAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'GET',
            $this->router->generate('api_v1_get_observation', array('observation' => 1))
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('observation', $content);
        $this->assertArrayHasKey('attachments', $content['observation']);
        $this->assertCount(2, $content['observation']['attachments']);
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::getPublicationObservationsAction()
     */
    public function testGetPublicationObservationsAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'GET',
            $this->router->generate('api_v1_get_publication_observations', array('publication' => 1))
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('observations', $content);
        $this->assertCount(1, $content['observations']);
    }




}