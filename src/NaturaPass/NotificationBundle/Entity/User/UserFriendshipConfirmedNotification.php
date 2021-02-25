<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 13/07/15
 * Time: 13:00
 */

namespace NaturaPass\NotificationBundle\Entity\User;

use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use NaturaPass\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserFriendshipConfirmedNotification
 * @package NaturaPass\NotificationBundle\Entity\User
 *
 * @ORM\Entity
 */
class UserFriendshipConfirmedNotification extends AbstractNotification {

    const TYPE = 'user.friendship.confirmed';

    protected $user;

    public function __construct(User $user) {
        parent::__construct(array(
            'route' => 'fos_user_profile_show_name',
        ));

        $this->user = $user;

        $this->objectID = $this->user->getId();
    }

    /**
     * Returns the data for the link to be created
     *
     * @return array
     */
    public function getLinkData() {
        return array(
            'usertag' => $this->sender->getUsertag()
        );
    }

    /**
     * Returns the data for the title to be created
     *
     * @return array
     */
    public function getContentData() {
        return array(
            '%sender%' => $this->sender->getFullName()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPushData() {
        return array_merge(parent::getPushData(), array(
            'element' => 'user',
            'user' => array(
                'id' => $this->sender->getId(),
                'usertag' => $this->sender->getUsertag()
            )
        ));
    }

}
