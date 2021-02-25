<?php

namespace Admin\ZoneBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Admin\SentinelleBundle\Entity\Zone;
use Doctrine\ORM\EntityRepository;

class ReceiverType extends AbstractType
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'receiver',
            'label' => 'receiver.attributes.name',
            'attr' => array(
                'placeholder' => 'receiver.placeholder.name'
            )
        ))
            ->add('photo', new ReceiverMediaType($this->container), array(
                'required' => false
            ));
    }

    public function getName()
    {
        return 'receiver';
    }

}
