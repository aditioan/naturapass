<?php

namespace Admin\SentinelleBundle\Form\Type;

use Admin\SentinelleBundle\Entity\CardLabel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardLabelType extends AbstractType
{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'card',
                'label' => 'card.attributes.name',
                'attr' => array(
                    'placeholder' => 'card.placeholder.name'
                )
            ))
            ->add('required', 'choice', array(
                'choices' => array(
                    1 => 'Obligatoire',
                    0 => 'Facultatif'
                ),
                'choices_as_values' => false,
                'empty_data' => 0,
                'required' => true
            ))
            ->add('type', 'choice', array(
                'choices' => array(
                    CardLabel::TYPE_STRING => 'String',
                    CardLabel::TYPE_TEXT => 'Text',
                    CardLabel::TYPE_INT => 'Integer',
                    CardLabel::TYPE_FLOAT => 'Float',
                    CardLabel::TYPE_DATE => 'Date'
                ),
                'choices_as_values' => false,
                'empty_data' => CardLabel::TYPE_STRING
            ))
            ->add('visible', 'integer');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\SentinelleBundle\Entity\CardLabel'
        ));
    }

    public function getName()
    {
        return 'label';
    }

}
