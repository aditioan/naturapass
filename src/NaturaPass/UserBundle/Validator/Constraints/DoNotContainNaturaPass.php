<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 04/09/14
 * Time: 16:54
 */

namespace NaturaPass\UserBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DoNotContainNaturaPass extends Constraint {
    public $message = 'validator.name.naturapass';

    public function validatedBy() {
        return 'naturapass_validator_username';
    }
}