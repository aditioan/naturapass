<?php

namespace NaturaPass\UserBundle\Form\Type;

use NaturaPass\UserBundle\Entity\PaperParameter;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaperParameterType extends AbstractType
{

    protected $container;
    protected $mode;
    protected $em;

    protected $user;

    public function __construct(User $user, $container)
    {
        $this->user = $user;
        $this->container = $container;
        $this->em = $container->get("doctrine")->getEntityManager();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'data_class' => 'NaturaPass\UserBundle\Entity\PaperParameter',
            'intention' => 'paper'
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $user = $this->user;

        $builder
            ->add('name', 'text', array(
                'required' => true
            ))
            ->add('text', 'text', array(
                'required' => false
            ))
//            ->add(
//                'type',
//                'choice',
//                array(
//                    'choices' => array(
//                        '2' => PaperParameter::ACCESS_MEDIA_NAME,
//                        '3' => PaperParameter::ACCESS_MEDIA_TEXT,
//                        '1' => PaperParameter::ACCESS_MEDIA,
//                        '0' => PaperParameter::TYPE_ALL,
//                    ),
//                    'choices_as_values' => true,
//                    'choice_value' => function ($choice) {
//                        return $choice;
//                    },
//                    'expanded' => true,
//                    'required' => false
//                )
//            )
//            ->add(
//                'medias',
//                new PaperMediaType($this->container),
//                array(
//                    'required' => false
//                )
//            );
            ->add('medias', 'collection', array(
                'type' => new PaperMediaType($this->container),
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            ));

        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($user) {
                $paper = $event->getForm()->getData();

                if (!$paper->getOwner()) {
                    $paper->setOwner($user);
                }

                $event->setData($paper);
            }
        );
    }

    public function getName()
    {
        return 'paper';
    }

}
