<?php

namespace NaturaPass\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NaturaPass\MainBundle\Entity\Sharing;

/**
 * Description of PublicationType
 *
 * @author vincentvalot
 */
class SharingType extends AbstractType {

    protected $share;

    public function __construct($default = false) {
        //$this->share = $default;
        $this->share = false;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('share', 'choice', array(
                    'label' => false,
                    'choices' => array(
                        'label.sharing.user' => Sharing::USER,
                        'label.sharing.friends' => Sharing::FRIENDS,
                        //'label.sharing.choice' => Sharing::KNOWING,
                        'label.sharing.naturapass' => Sharing::NATURAPASS,
                        'label.sharing.all' => Sharing::ALL,
                    ),
                    'choices_as_values' => true,
                    'choice_value' => function ($choice) {
                        return $choice;
                    },
                    'data' => $this->share ? $this->share->getShare() : Sharing::USER
                ))
                ->add('withouts', 'hidden', array(
                    'required' => false,
                    'mapped' => false
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\MainBundle\Entity\Sharing',
            'intention' => 'sharing'
        ));
    }

    public function getName() {
        return 'sharing';
    }

}
