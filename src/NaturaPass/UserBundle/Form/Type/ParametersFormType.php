<?php

namespace NaturaPass\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Description of ParametersType
 *
 * @author vincentvalot
 */
class ParametersFormType extends AbstractType {

    protected $securityContext;

    public function __construct(TokenStorageInterface $securityContext) {
        $this->securityContext = $securityContext;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\UserBundle\Entity\Parameters',
            'intention' => 'parameters'
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $user = $this->securityContext->getToken()->getUser();

        $builder->add('publication_sharing', new \NaturaPass\MainBundle\Form\Type\SharingType($user->getParameters() ? $user->getParameters()->getPublicationSharing() : false), array());

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($user) {
            $parameters = $event->getForm()->getData();

            $user->setParameters($parameters);

            $event->setData($user);
        });
    }

    public function getName() {
        return 'parameters';
    }

}
