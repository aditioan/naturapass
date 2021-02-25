<?php

namespace NaturaPass\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of PublicationType
 *
 * @author vincentvalot
 */
class BaseMediaType extends AbstractType {

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('name', 'text', array(
                'label' => 'publication.attributes.name',
                'attr' => array('placeholder' => 'publication.attributes.name'),
                'required' => false,
            ))
            ->add('file', 'file', array(
                'translation_domain' => $this->container->getParameter('translation_name').'publication',
                'label_attr' => array('class' => 'btn'),
                'label' => 'publication.attributes.file',
                'property_path' => null
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\MediaBundle\Entity\BaseMedia',
            'intention' => 'basemedia'
        ));
    }

    public function getName() {
        return 'basemedia';
    }

}
