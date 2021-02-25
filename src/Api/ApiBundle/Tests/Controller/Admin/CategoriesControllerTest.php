<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 29/06/15
 * Time: 10:17
 */

namespace Api\ApiBundle\Tests\Controller\Admin;


use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;

/**
 * Class ObservationsControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\ObservationsController
 */
class CategoriesControllerTest extends ApiControllerTest {

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::postCategoryAction()
     */
    public function testPostAdminCategoryAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'POST',
            $this->router->generate('api_admin_post_category'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'category' => array(
                    'name' => 'Catégorie 1',
                    'visible' => true
                )
            ))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::getCategoryAction()
     */
    public function testGetAdminCategoryAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'GET',
            $this->router->generate('api_admin_get_category', array('category' => '1'))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::postCategoryAction()
     */
    public function testPostAdminCategoryAction2() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'POST',
            $this->router->generate('api_admin_post_category'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'category' => array(
                    'name' => 'Catégorie 1.1',
                    'visible' => true,
                    'parent' => 1
                )
            ))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::putCategoryAction()
     */
    public function testPutAdminCategoryAction2() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'PUT',
            $this->router->generate('api_admin_put_category', array('category' => 1)),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'category' => array(
                    'name' => 'Catégorie 1 modifié',
                    'visible' => true
                )
            ))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::postCategoryAction()
     */
    public function testPostAdminCategoryAction3() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'POST',
            $this->router->generate('api_admin_post_category'),
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                'category' => array(
                    'name' => 'Catégorie 1.1.1',
                    'visible' => true,
                    'parent' => 2
                )
            ))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_CREATED, $this->clients->get('Vincent'));
    }

    /**
     * @covers \Api\ApiBundle\Controller\Admin\CategoriesController::getCategoryAllAction()
     */
    public function testGetAdminCategoryAllAction() {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request(
            'GET',
            $this->router->generate('api_admin_get_category_all')
        );

        $content = $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Vincent'));

        $this->assertArrayHasKey('tree', $content);
        $this->assertCount(1, $content['tree']);
    }
}