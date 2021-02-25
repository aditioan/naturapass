<?php

namespace NaturaPass\UserBundle\Security;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Description of Sha1Salted
 *
 * @author vincentvalot
 */
class Sha1Salted implements PasswordEncoderInterface {

    const SALT = 'W881xNaXSaPbcdOtrRTaM5ZNCp90qc3ti104uLMF';

    public function encodePassword($raw, $salt = false) {
        return hash('sha1', self::SALT . $raw); // Custom function for encrypt
    }

    public function isPasswordValid($encoded, $raw, $salt = NULL) {
        return $encoded === $this->encodePassword($raw, self::SALT);
    }

}
