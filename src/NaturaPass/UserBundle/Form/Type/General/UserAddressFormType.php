<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 01/09/14
 * Time: 16:52
 */

namespace NaturaPass\UserBundle\Form\Type\General;


use NaturaPass\MainBundle\Form\Type\GeolocationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserAddressFormType extends GeolocationType
{

    protected $securityContext;
    protected $container;

    public function __construct(TokenStorageInterface $securityContext, $container)
    {
        $this->securityContext = $securityContext;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('favorite', 'hidden', array('required' => false))
            ->add('title', 'text', array('required' => true));

        $user = $this->securityContext->getToken()->getUser();

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($user) {
            $address = $event->getForm()->getData();

            if (!$address->getOwner()) {
                $address->setOwner($user);
            }

            if (!$address->isFavorite()) {
                $address->setFavorite(false);
            }

            $event->setData($address);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\UserBundle\Entity\UserAddress',
            'intention' => 'useraddress'
        ));
    }

    public function getName()
    {
        return 'address';
    }

}