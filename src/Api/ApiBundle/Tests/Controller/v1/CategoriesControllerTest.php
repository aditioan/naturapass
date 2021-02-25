<?php

namespace Api\ApiBundle\Tests\Controller\v1;

use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;

/**
 * Class CategoriesControllerTest
 * @package Api\ApiBundle\Tests\Controller
 *
 * @covers \Api\ApiBundle\Controller\v1\CategoriesController
 */
class CategoriesControllerTest extends ApiControllerTest {

    /**
     * @covers \Api\ApiBundle\Controller\v1\CategoriesController::getCategoryAllAction()
     */
    public function testGetCategoryAllAction() {
        $this->logClientIn($this->clients->get('Nicolas'));

        $this->clients->get('Vincent')->request(
                'GET', $this->router->generate('api_v1_get_category_all', array('publication' => '728'))
        );

        $this->assertStatusCodeEquals(Codes::HTTP_OK, $this->clients->get('Nicolas'));
    }

}
