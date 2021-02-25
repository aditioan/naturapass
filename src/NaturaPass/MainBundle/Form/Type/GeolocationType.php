<?php

namespace NaturaPass\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of PublicationFormType
 *
 * @author vincentvalot
 */
class GeolocationType extends AbstractType {

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('latitude', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name').'main',
            'label' => 'geolocation.attributes.latitude',
            'attr' => array(
                'class' => 'publication_geolocation_latitude',
                'autocomplete' => 'off'
            )
        ))->add('longitude', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name').'main',
            'label' => 'geolocation.attributes.longitude',
            'attr' => array(
                'class' => 'publication_geolocation_longitude',
                'autocomplete' => 'off'
            )
        ))->add('address', 'text', array(
                    'translation_domain' => $this->container->getParameter('translation_name').'main',
                    'label' => 'geolocation.attributes.address',
                    'attr' => array(
                        'class' => 'publication_geolocation_address',
                        'autocomplete' => 'off'
                    )
                ))
            ->add('altitude', 'text', array(
                    'required' => false,
                    'translation_domain' => $this->container->getParameter('translation_name').'main',
                    'label' => 'geolocation.attributes.altitude',
                    'attr' => array(
                        'autocomplete' => 'off'
                    )
                ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\MainBundle\Entity\Geolocation',
            'intention' => 'geolocation'
        ));
    }

    public function getName() {
        return 'geolocation';
    }

}
