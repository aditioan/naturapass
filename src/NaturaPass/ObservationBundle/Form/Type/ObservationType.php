<?php

namespace NaturaPass\ObservationBundle\Form\Type;

use NaturaPass\PublicationBundle\Entity\Publication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObservationType extends AbstractType
{

    protected $publication;

    public function __construct(Publication $publication)
    {
        $this->publication = $publication;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category', 'entity', array(
                'class' => 'AdminSentinelleBundle:Category'
            ))
            ->add('attachments', 'collection', array(
                'type' => new AttachmentType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                "required" => false
            ))
            ->add('specific', 'choice', array(
                'choices' => array(
                    0,
                    1
                ),
                'choices_as_values' => false,
                'empty_data' => 0
            ))
            ->add('animal', 'entity', array(
                'class' => 'AdminAnimalBundle:Animal',
                "required" => false
            ));
//            ->add('receivers', 'collection', array(
//                'type' => new ReceiverType(),
//                'allow_add' => true,
//                'allow_delete' => true,
//                'by_reference' => false,
//                "required" => false
//            ));

        $publication = $this->publication;
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($publication) {
            $observation = $event->getForm()->getData();

            if (!$observation->getPublication()) {
                $observation->setPublication($publication);
            }

            $event->setData($observation);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\ObservationBundle\Entity\Observation'
        ));
    }

    public function getName()
    {
        return 'observation';
    }

}
