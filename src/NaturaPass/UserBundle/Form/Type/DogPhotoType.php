<?php

namespace NaturaPass\UserBundle\Form\Type;

use NaturaPass\MediaBundle\Form\Type\BaseMediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of DogPhotoType
 *
 */
class DogPhotoType extends BaseMediaType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->remove('name');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\UserBundle\Entity\DogPhoto',
            'intention' => 'dogphoto'
        ));
    }

    public function getName()
    {
        return 'dogphoto';
    }

}
