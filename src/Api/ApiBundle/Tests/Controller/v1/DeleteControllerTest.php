<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 05/09/14
 * Time: 11:50
 */

namespace Api\ApiBundle\Tests\Controller\v1;

use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;

class DeleteControllerTest extends ApiControllerTest {

    public function setUp() {
        parent::setUp();
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\ObservationsController::deleteObservationAction()
     */
    public function testDeleteObservationAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'DELETE',
            $this->router->generate('api_v1_delete_observation', array('observation' => 1))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CardsController::deleteCardAction()
     */
    public function testDeleteAdminCardAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'DELETE',
            $this->router->generate('api_admin_delete_card', array('card' => 1))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::deleteCategorySinglenodeAction()
     */
    public function testDeleteAdminCategorySinglenodeAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'DELETE',
            $this->router->generate('api_admin_delete_category_singlenode', array('category' => 3))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::deleteCategoryAction()
     */
    public function testDeleteAdminCategoryAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'DELETE',
            $this->router->generate('api_admin_delete_category', array('category' => 1))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * Suppression d'une publication
     *
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::deletePublicationAction()
     */
    public function testDeletePublication() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_publication', array('publication' => 1)));

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\UsersController::deleteUserAction()
     */
    public function testDeleteUser() {
        foreach ($this->clients as $client) {
            $this->logClientIn($client);

            $client->request('DELETE', $this->router->generate('api_v1_delete_user'),
                array(),
                array(),
                array('CONTENT_TYPE' => 'application/json'),
                json_encode(array('password' => $client->getUser()->getPassword())));

            $this->assertStatusCodeEquals(Codes::HTTP_OK, $client);
        }
    }
} 