<?php

namespace NaturaPass\UserBundle\Form\Type;

use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\DogParameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DogParameterType extends AbstractType
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
            'data_class' => 'NaturaPass\UserBundle\Entity\DogParameter',
            'intention' => 'dog'
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $user = $this->user;

        $builder->add('name', 'text', array(
            'required' => false
        ))
            ->add('breed', 'entity', array(
                'class' => "NaturaPass\UserBundle\Entity\DogBreed",
                'choices' => $this->getBreed(),
                'choice_label' => 'name',
                'required' => false,
            ))
            ->add('type', 'entity', array(
                'class' => "NaturaPass\UserBundle\Entity\DogType",
                'choices' => $this->getType(),
                'choice_label' => 'name',
                'required' => false,
            ))
            ->add(
                'sex',
                'choice',
                array(
                    'choices' => array(
                        '0' => DogParameter::SEX_MALE,
                        '1' => DogParameter::SEX_FEMALE
                    ),
                    'choices_as_values' => true,
                    'choice_value' => function ($choice) {
                        return $choice;
                    },
                    'expanded' => true
                )
            )
            ->add('birthday', 'datetime', array(
                'widget' => 'single_text',
                'required' => false
            ))
            ->add(
                'photo',
                new DogPhotoType($this->container),
                array(
                    'required' => false
                )
            )
            ->add('medias', 'collection', array(
                'type' => new DogMediaType($this->container),
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            ));

        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($user) {
                $dog = $event->getForm()->getData();

                if (!$dog->getOwner()) {
                    $dog->setOwner($user);
                }

                $event->setData($dog);
            }
        );
    }

    public function getBreed()
    {
        $breeds = $this->em->getRepository("NaturaPassUserBundle:DogBreed")->findAll();
        return $breeds;
    }

    public function getType()
    {
        $types = $this->em->getRepository("NaturaPassUserBundle:DogType")->findAll();
        return $types;
    }

    public function getName()
    {
        return 'dog';
    }

}
