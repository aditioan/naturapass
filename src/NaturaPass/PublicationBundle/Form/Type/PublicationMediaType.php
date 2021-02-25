<?php

namespace NaturaPass\PublicationBundle\Form\Type;

use NaturaPass\MainBundle\Form\Type\SharingType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of PublicationFormType
 *
 * @author vincentvalot
 */
class PublicationMediaType extends AbstractType
{

    protected $share;
    protected $container;

    public function __construct($share = false, $container)
    {
        $this->share = $share;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('legend', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'publication',
            'label' => 'publication.attributes.legend',
            'attr' => array('placeholder' => 'publication.attributes.legend'),
            'required' => false
        ))
            ->add('file', 'file', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'publication',
                'label' => 'publication.attributes.media',
                'property_path' => null,
                'required' => false
            ))
            ->add('tags', 'hidden', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'publication',
                'label' => 'publication.attributes.tags',
                'mapped' => false,
                'property_path' => null,
                'required' => false
            ))
            ->add('sharing', new SharingType($this->share), array(
                'label' => false,
                'attr' => array('class' => 'hide'),
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\PublicationBundle\Entity\PublicationMedia',
            'intention' => 'publicationmedia'
        ));
    }

    public function getName()
    {
        return 'media'; 
    }

}
