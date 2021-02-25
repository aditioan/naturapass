<?php

namespace Admin\ZoneBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Admin\SentinelleBundle\Entity\Zone;
use Doctrine\ORM\EntityRepository;

class ZoneType extends AbstractType
{

    public function __construct()
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
            'translation_domain' => 'zone',
            'label' => 'zone.attributes.name',
            'attr' => array(
                'placeholder' => 'zone.placeholder.name'
            )
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $zone = $event->getData();
            $form = $event->getForm();

            if (!$zone || null === $zone->getId()) {
                $form->add('localities', 'entity', array(
                    'translation_domain' => 'zone',
                    'label' => 'zone.attributes.localities',
                    'required' => true,
                    'class' => 'AdminSentinelleBundle:Locality',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('l')
                            ->where('l.zone is NULL')
                            ->orderBy('l.postal_code', 'ASC');
                    },
                    'multiple' => true
                ));
            } else {
                $form->add('localities', 'entity', array(
                    'translation_domain' => 'zone',
                    'label' => 'zone.attributes.localities',
                    'required' => true,
                    'class' => 'AdminSentinelleBundle:Locality',
                    'query_builder' => function (EntityRepository $er) use ($zone) {
                        return $er->createQueryBuilder('l')
                            ->where('l.zone is NULL')
                            ->orWhere('l.zone = :id_zone')
                            ->setParameter('id_zone', $zone->getId())
                            ->orderBy('l.postal_code', 'ASC');
                    },
                    'multiple' => true
                ));
            }
        });
    }

    public function getName()
    {
        return 'zone';
    }

}
