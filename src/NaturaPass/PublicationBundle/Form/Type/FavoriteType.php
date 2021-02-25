<?php

namespace NaturaPass\PublicationBundle\Form\Type;

use Admin\SentinelleBundle\Entity\Card;
use NaturaPass\MainBundle\Form\Type\SharingType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FavoriteType extends AbstractType
{

    protected $container;
    protected $securityContext;
    protected $em;

    public function __construct(TokenStorageInterface $securityContext, $container)
    {
        $this->securityContext = $securityContext;
        $this->container = $container;
        $this->em = $container->get("doctrine")->getEntityManager();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->securityContext->getToken()->getUser();

        $builder
            ->add('name', 'text', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'favorite',
                'label' => 'favorite.attributes.name',
                'attr' => array(
                    'placeholder' => 'favorite.placeholder.name'
                )
            ))
            ->add('sharing', new SharingType($user->getParameters() ? $user->getParameters()->getPublicationSharing() : false), array(
                'label' => false,
                'attr' => array('class' => 'hide'),
                'required' => false,
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
            ))
            ->add('category', 'entity', array(
                'class' => 'AdminSentinelleBundle:Category',
                'required' => false,
            ))
            ->add('card', 'entity', array(
                'class' => 'AdminSentinelleBundle:Card',
                'required' => false
            ))
            ->add('attachments', 'collection', array(
                'type' => new FavoriteAttachmentType(),
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
        $builder->addEventListener(
            \Symfony\Component\Form\FormEvents::SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($user) {
                $favorite = $event->getForm()->getData();

                if (!$favorite->getOwner()) {
                    $favorite->setOwner($user);
                }

                foreach ($favorite->getAttachments() as $attachment) {
                    if (is_null($attachment->getValue())) {
//                        $attachment->setValue("");
//                        if (!is_null($attachment->getLabel()) && $attachment->getLabel()->allowContentType()) {
                        $favorite->removeAttachment($attachment);
//                        }
                    }
                }

                $event->setData($favorite);
            }
        );
    }

    public function getColor()
    {
        $colors = $this->em->getRepository("NaturaPassPublicationBundle:PublicationColor")->findBy(array("active" => 1));
        return $colors;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'NaturaPass\PublicationBundle\Entity\Favorite'
        ));
    }

    public function getName()
    {
        return 'favorite';
    }

}
