<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 25/04/14
 * Time: 08:35
 */

namespace NaturaPass\LoungeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use NaturaPass\LoungeBundle\Entity\Lounge;

class LoungeEditionType extends AbstractType
{
    protected $lounge;
    protected $container;

    public function __construct(Lounge $lounge,$container)
    {
        $this->lounge = $lounge;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            array(
                'translation_domain' => $this->container->getParameter('translation_name') .'lounge',
                'label' => 'lounge.attributes.name',
                'attr' => array(
                    'placeholder' => 'lounge.placeholder.name'
                )
            )
        )
            ->add(
                'description',
                'textarea',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'lounge',
                    'label' => 'lounge.attributes.description',
                    'required' => false,
                    'attr' => array(
                        'placeholder' => 'lounge.placeholder.description'
                    )
                )
            )
            ->add(
                'geolocation',
                'hidden',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'lounge',
                    'required' => false
                )
            )->add(
                'access',
                'choice',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'lounge',
                    'label' => 'lounge.attributes.access',
                    'choices' => array(
                        'lounge.attributes.access_list.protected' => Lounge::ACCESS_PROTECTED,
                        'lounge.attributes.access_list.semiprotected' => Lounge::ACCESS_SEMIPROTECTED,
                        'lounge.attributes.access_list.public' => Lounge::ACCESS_PUBLIC
                    ),
                    'choices_as_values' => true,
                    'choice_value' => function ($choice) {
                        return $choice;
                    },
                    'expanded' => true
                )
            )
            ->add(
                'meetingDate',
                'datetime',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'lounge',
                    'label' => 'lounge.attributes.meetingDate',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy HH:mm',
                    'required' => false,
                    'attr' => array(
                        'placeholder' => 'lounge.placeholder.meetingDate'
                    )
                )
            )
            ->add(
                'endDate',
                'datetime',
                array(
                    'translation_domain' =>$this->container->getParameter('translation_name') .'lounge',
                    'label' => 'lounge.attributes.endDate',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy HH:mm',
                    'attr' => array(
                        'placeholder' => 'lounge.placeholder.endDate'
                    )
                )
            )
            ->add(
                'meetingAddress',
                'text',
                array(
                    'translation_domain' => $this->container->getParameter('translation_name') .'lounge',
                    'label' => 'lounge.attributes.meetingAddress',
                    'required' => false,
                    'attr' => array(
                        'placeholder' => 'lounge.placeholder.meetingAddress'
                    )
                )
            )
            ->add(
                'photo',
                new LoungeMediaType($this->container),
                array(
                    'required' => false
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'NaturaPass\LoungeBundle\Entity\Lounge'
            )
        );
    }

    public function getName()
    {
        return 'lounge';
    }

}