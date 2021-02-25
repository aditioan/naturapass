<?php

namespace NaturaPass\PublicationBundle\Form\Type;

use NaturaPass\MainBundle\Form\Type\GeolocationType;
use NaturaPass\MainBundle\Form\Type\SharingType;
use NaturaPass\PublicationBundle\Form\EventListener\PublicationEventSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Description of PublicationFormType
 *
 * @author vincentvalot
 */
class PublicationFormType extends AbstractType
{

    protected $securityContext;
    protected $container;
    protected $mode;
    protected $em;

    public function __construct(TokenStorageInterface $securityContext, $container, $mode = '')
    {
        $this->securityContext = $securityContext;
        $this->container = $container;
        $this->mode = $mode;
        $this->em = $container->get("doctrine")->getEntityManager();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'data_class' => 'NaturaPass\PublicationBundle\Entity\Publication',
            'intention' => 'publication'
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $user = $this->securityContext->getToken()->getUser();

        $builder->add('content', 'textarea', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'publication',
            'label' => false,
            'attr' => array('placeholder' => 'publication.action.type_text', 'rows' => '3', 'class' => ''),
            'required' => false
        ))
            ->add('created', 'datetime', array(
                'attr' => array('class' => 'hide'),
                'widget' => 'single_text',
                'required' => false
            ))
            ->add('date', 'datetime', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'publication',
                'label' => false,
                'attr' => array('placeholder' => 'publication.attributes.date', 'autocomplete' => 'off'),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'required' => false
            ))
            ->add('sharing', new SharingType($user->getParameters() ? $user->getParameters()->getPublicationSharing() : false), array(
                'label' => false,
                'attr' => array('class' => 'hide'),
            ))
            ->add('geolocation', new GeolocationType($this->container), array(
                'attr' => array('class' => 'hide'),
                'required' => false
            ))
            ->add('media', new PublicationMediaType($user->getParameters() ? $user->getParameters()->getPublicationSharing() : false, $this->container), array(
                'label' => false,
                'required' => false
            ))
            ->add('landmark', 'checkbox', array(
                'label' => false,
                'required' => false
            ))
            ->add('legend', 'text', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'publication',
                'label' => 'publication.attributes.legend',
                'attr' => array('placeholder' => 'publication.attributes.legend'),
                'required' => false
            ))
            ->add('groups', 'hidden', array(
                'required' => false,
                'mapped' => false
            ))
	        ->add('users', 'hidden', array(
                'required' => false,
                'mapped' => false
            ))
            ->add('hunts', 'hidden', array(
                'required' => false,
                'mapped' => false
            ))
            ->add('special', 'hidden', array(
                'required' => false,
                'mapped' => false
            ))
            ->add('guid', 'text', array(
                'required' => false,
            ))
            ->add('publicationcolor', 'entity', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'publication',
                'class' => "NaturaPass\PublicationBundle\Entity\PublicationColor",
                'choices' => $this->getColor(),
                'choice_label' => 'name',
                'placeholder' => "label.color_disable",
                'required' => false,
                'choice_attr' => function ($allChoices, $currentChoiceKey) {
                    return array('data-color' => "#" . $allChoices->getColor());
                },
            ));

        $builder->addEventSubscriber(new PublicationEventSubscriber($this->securityContext));
    }

    public function getColor()
    {
        $colors = $this->em->getRepository("NaturaPassPublicationBundle:PublicationColor")->findBy(array("active" => 1));
        return $colors;
    }

    public function getName()
    {
        return 'publication' . $this->mode;
    }


}
