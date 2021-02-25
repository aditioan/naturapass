<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 01/09/14
 * Time: 08:55
 */

namespace NaturaPass\MainBundle\Tests;


class SeleniumTestController extends \PHPUnit_Extensions_SeleniumTestCase {

    public static $browsers = array(
        array(
            'name'    => 'Firefox on Linux',
            'browser' => '*firefox'
        ),
        array(
            'name'    => 'Google Chrome on Linux',
            'browser' => '*chrome'
        )
    );

    public static $seleneseDirectory = __DIR__;

    protected function setUp() {
        $this->shareSession(true);
        $this->setBrowserUrl('http://www.naturapass.e-conception.fr/app_dev.php/');
    }
} 