<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 06/07/15
 * Time: 20:08
 */

namespace Api\ApiBundle\Tests\Controller\v1;


use Api\ApiBundle\Tests\ApiControllerTest;
use FOS\RestBundle\Util\Codes;
use NaturaPass\MainBundle\Entity\Sharing;
use NaturaPass\MediaBundle\Entity\BaseMedia;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class VideosControllerTest extends ApiControllerTest {

    protected function generateVideoTest($name, $mime) {
        $this->logClientIn($this->clients->get('Vincent'));

        $this->clients->get('Vincent')->request('POST', $this->router->generate('api_v1_post_publication'),
            array(),
            array('publication' =>
                array('media' =>
                    array(
                        'file' => new UploadedFile($this->getRootDir() . 'web/tests/' . $name, $name, $mime)
                    ))
            ),
            array(
                'CONTENT_TYPE' => 'application/json'
            ),
            json_encode(array(
                    'publication' => array(
                        'content' => "Publication d'une vidéo " . $mime,
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
        $this->assertArrayHasKey('media', $content['publication']);
        $this->assertArrayHasKey('type', $content['publication']['media']);
        $this->assertEquals(BaseMedia::TYPE_VIDEO, $content['publication']['media']['type']);

        $this->clients->get('Vincent')->request('DELETE', $this->router->generate('api_v1_delete_publication', array('publication' => $content['publication']['id'])));
        $this->assertEquals(Codes::HTTP_OK, $this->clients->get('Vincent')->getResponse()->getStatusCode(), $this->getErrorMessage($this->clients->get('Vincent')->getResponse()));
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationAction()
     */
    public function testPostPublicationVideoMP4() {
        $this->generateVideoTest('video_mp4.mp4', 'video/mp4');
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationAction()
     */
    public function testPostPublicationVideoMov() {
        $this->generateVideoTest('video_mov.mov', 'video/quicktime');
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationAction()
     */
    public function testPostPublicationVideo3GP() {
        $this->generateVideoTest('video_3gp.3gp', 'video/3gpp');
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationAction()
     */
    public function testPostPublicationVideoFLV() {
        $this->generateVideoTest('video_flv.flv', 'video/x-flv');
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationAction()
     */
    public function testPostPublicationVideoMpeg1() {
        $this->generateVideoTest('video_mpg1.mpg', 'video/mpeg');
    }

    /**
     * @covers \Api\ApiBundle\Controller\v1\PublicationsController::postPublicationAction()
     */
    public function testPostPublicationVideoAvi() {
        $this->generateVideoTest('video_avi.avi', 'video/x-msvideo');
    }

}