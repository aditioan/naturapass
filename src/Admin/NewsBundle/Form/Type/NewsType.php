<?php

namespace Admin\NewsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Admin\NewsBundle\Entity\News;

class NewsType extends AbstractType
{

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array(
            'translation_domain' => $this->container->getParameter('translation_name') . 'news',
            'label' => 'news.attributes.title',
            'required' => true,
            'attr' => array(
                'placeholder' => 'news.placeholder.title'
            )
        ))
            ->add('link', 'text', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'news',
                'label' => 'news.attributes.link',
                'required' => false,
                'attr' => array(
                    'placeholder' => 'news.placeholder.link'
                )
            ))
            ->add('content', 'textarea', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'news',
                'label' => 'news.attributes.content',
                'attr' => array(
                    'placeholder' => 'news.placeholder.content'
                )
            ))
            ->add('active', 'choice', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'news',
                'label' => 'news.title.active',
                'choices' => array(
                    "1" => 'news.title.active_yes',
                    "0" => 'news.title.active_no'
                ),
                'choices_as_values' => false,
                'expanded' => true
            ))
            ->add('date', 'datetime', array(
                'translation_domain' => $this->container->getParameter('translation_name') . 'news',
                'label' => 'news.attributes.date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm',
                'attr' => array(
                    'placeholder' => 'news.placeholder.date'
                )
            ))
            ->add('photo', new NewsMediaType($this->container), array(
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\NewsBundle\Entity\News'
        ));
    }

    public function getName()
    {
        return 'news';
    }

}
