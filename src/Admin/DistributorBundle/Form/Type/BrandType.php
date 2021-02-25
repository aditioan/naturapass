<?php

namespace Admin\DistributorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Admin\DistributorBundle\Entity\Brand;
use Doctrine\ORM\EntityRepository;

class BrandType extends AbstractType
{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'brand',
            'label' => 'brand.attributes.name',
            'attr' => array(
                'placeholder' => 'brand.placeholder.name'
            )
        ))
            ->add('partner', 'hidden', array(
                'translation_domain' => 'brand',
                'required' => false
            ))
            ->add('logo', new BrandMediaType($this->container), array(
                'required' => false
            ));
    }

    public function getName()
    {
        return 'brand';
    }

}
