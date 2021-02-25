<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 04/09/14
 * Time: 16:44
 */

namespace NaturaPass\UserBundle\Validator\Constraints;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DoNotContainNaturaPassValidator extends ConstraintValidator
{
    protected $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint)
    {
        if (preg_match('/naturapass/i', $value, $matches)) {
            $this->context->addViolation($this->translator->trans($constraint->message, array(), 'user'));
        }
    }
}