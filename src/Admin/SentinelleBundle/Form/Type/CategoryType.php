<?php

namespace Admin\SentinelleBundle\Form\Type;

use Admin\SentinelleBundle\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('name', 'text')
            ->add('visible', 'choice', array(
                'choices' => array(
                    Category::VISIBLE_ON => 'Visible',
                    Category::VISIBLE_OFF => 'Non visible'
                ),
                'choices_as_values' => false,
                'empty_data' => Category::VISIBLE_ON
            ))
            ->add('type', 'choice', array(
                'choices' => array(
                    Category::TYPE_ALL => 'Tout',
                ),
                'choices_as_values' => false,
                'empty_data' => Category::TYPE_ALL
            ))
            ->add('parent', 'entity', array(
                'class' => 'AdminSentinelleBundle:Category',
                'required' => false,
                'empty_data' => null
            ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\SentinelleBundle\Entity\Category'
        ));
    }

    public function getName() {
        return 'category';
    }

}
