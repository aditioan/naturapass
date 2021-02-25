<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 24/06/15
 * Time: 10:06
 */

namespace Admin\SentinelleBundle\DataFixtures\ORM;

use Admin\SentinelleBundle\Entity\Entity;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadReceiverData implements FixtureInterface {

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager) {
        $federation = new \Admin\SentinelleBundle\Entity\Receiver();

        $federation->setName('Fédération de test');

        $manager->persist($federation);
        $manager->flush();
    }

}
