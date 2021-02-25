<?php

/**
 * Created by PhpStorm.
 * User: nicolasmendez
 * Date: 28/07/15
 * Time: 10:31
 */

namespace Api\ApiBundle\Controller\v2\Serialization;

use Admin\NewsBundle\Entity\News;
use Admin\NewsBundle\Entity\NewsMedia;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * Class NewsSerialization
 * @package Api\ApiBundle\Controller\v2\Serialization
 *
 * @method static serializeNews(array $news)
 */
class NewsSerialization extends Serialization
{

    /**
     * @param News $new
     * @return array
     */
    public static function serializeNew(News $new)
    {
        $array = array(
            'id' => $new->getId(),
            'title' => $new->getTitle(),
            'date' => $new->getDate(),
            'active' => $new->getActive(),
            'content' => $new->getContent(),
            'sub_content' => strlen($new->getContent()) > 255 ? substr($new->getContent(), 0, 255)." [...]":$new->getContent(),
            'link' => $new->getLink(),
            'photo' => $new->getPhoto() instanceof NewsMedia ? MainSerialization::serializeNewsMedia($new->getPhoto()) : null,
        );
        return $array;
    }
}
