<?php

namespace NaturaPass\UserBundle\Form\Type;

use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\WeaponParameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WeaponType extends AbstractType
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
            'data_class' => 'NaturaPass\UserBundle\Entity\WeaponParameter',
            'intention' => 'weapon'
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $user = $this->user;

        $builder->add('name', 'text', array(
            'required' => false
        ))
            ->add('calibre', 'entity', array(
                'class' => "NaturaPass\UserBundle\Entity\WeaponCalibre",
                'choices' => $this->getCalibre(),
                'choice_label' => 'name',
                'required' => false,
            ))
            ->add('brand', 'entity', array(
                'class' => "NaturaPass\UserBundle\Entity\WeaponBrand",
                'choices' => $this->getBrand(),
                'choice_label' => 'name',
                'required' => false,
            ))
            ->add(
                'type',
                'choice',
                array(
                    'choices' => array(
                        '1' => WeaponParameter::TYPE_CARABINE,
                        '0' => WeaponParameter::TYPE_SHOTGUN
                    ),
                    'choices_as_values' => true,
                    'choice_value' => function ($choice) {
                        return $choice;
                    },
                    'expanded' => true
                )
            )
            ->add(
                'photo',
                new WeaponPhotoType($this->container),
                array(
                    'required' => false
                )
            )
            ->add('medias', 'collection', array(
                'type' => new WeaponMediaType($this->container),
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            ));

        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($user) {
                $weapon = $event->getForm()->getData();

                if (!$weapon->getOwner()) {
                    $weapon->setOwner($user);
                }

                $event->setData($weapon);
            }
        );
    }

    public function getCalibre()
    {
        $calibres = $this->em->getRepository("NaturaPassUserBundle:WeaponCalibre")->findAll();
        return $calibres;
    }

    public function getBrand()
    {
        $calibres = $this->em->getRepository("NaturaPassUserBundle:WeaponBrand")->findAll();
        return $calibres;
    }

    public function getName()
    {
        return 'weapon';
    }

}
