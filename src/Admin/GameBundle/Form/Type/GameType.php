<?php

namespace Admin\GameBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Admin\GameBundle\Entity\Game;

class GameType extends AbstractType
{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'game',
            'label' => 'game.attributes.title',
            'required' => true,
            'attr' => array(
                'placeholder' => 'game.placeholder.title'
            )
        ))
            ->add('color', 'text', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.color',
                'required' => true,
                'attr' => array(
                    'placeholder' => 'game.placeholder.color'
                )
            ))
            ->add('type', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.type.label',
                'choices' => array(
                    Game::TYPE_GAME => 'game.type.type_list.game',
                    Game::TYPE_CHALLENGE => 'game.type.type_list.challenge'
                ),
                'choices_as_values' => false,
                'expanded' => true
            ))
            ->add('debut', 'datetime', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.debut',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'required' => false,
                'attr' => array(
                    'placeholder' => 'game.placeholder.debut'
                )
            ))
            ->add('fin', 'datetime', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.fin',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'attr' => array(
                    'placeholder' => 'game.placeholder.fin'
                )
            ))
            ->add('top1', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.top1',
                'attr' => array(
                    'placeholder' => 'game.placeholder.top1'
                ),
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'advanced')))
            ->add('top2', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.top2',
                'attr' => array(
                    'placeholder' => 'game.placeholder.top2'
                ),
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'advanced')))
            ->add('titleExplanation', 'text', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.titleExplanation',
                'required' => true,
                'attr' => array(
                    'placeholder' => 'game.placeholder.titleExplanation'
                )
            ))
            ->add('explanation', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.explanation',
                'attr' => array(
                    'placeholder' => 'game.placeholder.explanation'
                ),
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'advanced')))
            ->add('reglement', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.reglement',
                'attr' => array(
                    'placeholder' => 'game.placeholder.reglement'
                ),
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'advanced')))
            ->add('challenge', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.challenge',
                'attr' => array(
                    'placeholder' => 'game.placeholder.challenge'
                ),
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'advanced')))
            ->add('visuel', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.visuel',
                'attr' => array(
                    'placeholder' => 'game.placeholder.visuel'
                ),
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'advanced')))
            ->add('resultat', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'game',
                'label' => 'game.attributes.resultat',
                'attr' => array(
                    'placeholder' => 'game.placeholder.resultat'
                ),
                'attr' => array(
                    'class' => 'tinymce',
                    'data-theme' => 'advanced')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\GameBundle\Entity\Game'
        ));
    }

    public function getName()
    {
        return 'game';
    }

}
