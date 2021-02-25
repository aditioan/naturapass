<?php

namespace Admin\DistributorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Admin\DistributorBundle\Entity\Distributor;
use NaturaPass\MainBundle\Form\Type\GeolocationType;
use Doctrine\ORM\EntityRepository;

class DistributorType extends AbstractType {

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
//        echo "<pre>";
//        print_r($options);
//        echo "</pre>";
        $builder->add('name', 'text', array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'distributor',
                    'label' => 'distributor.attributes.name',
                    'attr' => array(
                        'placeholder' => 'distributor.placeholder.name'
                    )
                ))
                ->add('address', 'text', array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'distributor',
                    'label' => 'distributor.attributes.address',
                    'attr' => array(
                        'placeholder' => 'distributor.placeholder.address'
                    )
                ))
                ->add('cp', 'text', array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'distributor',
                    'label' => 'distributor.attributes.cp',
                    'attr' => array(
                        'placeholder' => 'distributor.placeholder.cp'
                    )
                ))
                ->add('city', 'text', array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'distributor',
                    'label' => 'distributor.attributes.city',
                    'attr' => array(
                        'placeholder' => 'distributor.placeholder.city'
                    )
                ))
                ->add('telephone', 'text', array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'distributor',
                    'label' => 'distributor.attributes.telephone',
                    'attr' => array(
                        'placeholder' => 'distributor.placeholder.telephone'
                    )
                ))
                ->add('email', 'text', array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'distributor',
                    'label' => 'distributor.attributes.email',
                    'attr' => array(
                        'placeholder' => 'distributor.placeholder.email'
                    )
                ))
                ->add('geolocation', new GeolocationType($this->container), array(
                    'label' => false,
                    'required' => false,
                    'attr' => array('class' => 'hide'),
                ))
                ->add('brands', 'entity', array(
                    'required' => true,
                    'class' => 'AdminDistributorBundle:Brand',
                    'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('b')
                                ->orderBy('b.name', 'ASC');
                    },
                    'multiple' => true
                ))
                ->add('logo', new DistributorMediaType($this->container), array(
                    'required' => false
        ));
    }

    public function getName() {
        return 'distributor';
    }

}
