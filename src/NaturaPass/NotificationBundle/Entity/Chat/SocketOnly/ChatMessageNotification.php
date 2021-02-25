<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 14/07/15
 * Time: 10:57
 */

namespace NaturaPass\NotificationBundle\Entity\Chat\SocketOnly;

use Api\ApiBundle\Controller\v1\ApiRestController;
use NaturaPass\MessageBundle\Entity\Message;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ChatMessageNotification
 * @package NaturaPass\NotificationBundle\Entity\Chat\SocketOnly
 *
 * @ORM\Entity
 */
class ChatMessageNotification extends AbstractNotification
{

    const TYPE = 'chat.new_message';

    private $message;
    private $formated;

    public function __construct(Message $message, $formated)
    {
        parent::__construct(array(
            'persistable' => false,
            'socket' => array(
                'enabled' => true,
                'event_name' => 'api-chat-message:incoming'
            ),
            'push' => array(
                'enabled' => true,
                'silent' => false
            )
        ));


        $this->message = $message;
        $this->formated = $formated;
        $this->objectID = $message->getConversation()->getId();
    }

    /**
     * Return the pool name
     *
     * @return string
     */
    public function getPoolName()
    {
        return "conversation" . $this->message->getConversation()->getId();
    }

    public function getSocketData()
    {
        return $this->formated;
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData()
    {
        // TODO: Implement getLinkData() method.
    }

    /**
     * Returns the data for the title to be created
     *
     * @return array
     */
    public function getContentData()
    {
        return array(
            '%sender%' => $this->message->getOwner()->getFullName(),
            '%text%' => $this->message->getContent()
        );
    }

    /**
     * Returns the data for the push data to be created
     *
     * @return array
     */
    public function getPushData()
    {
        return array_merge(parent::getPushData(), array(
            'element' => 'message'
        ));
    }

}
