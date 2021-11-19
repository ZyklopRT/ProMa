<?php

namespace jjansen\Form\Type;

use jjansen\Entity\Team;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => false,
                'row_attr' => [
                    'class' => 'd'
                ],
                'attr' => [
                    'placeholder' => 'Team-Name...'
                ]
            ])
            ->add('quickid', TextType::class, [
                'required' => false,
                'label' => 'Kürzel',
                'attr' => [
                    'placeholder' => '#12345abcde'
                ]
            ])
            ->add('invitation', ChoiceType::class, [
                'choices' => [
                    'geschlossen' => 1,
                    'offen' => 0
                ],
                'required' => true,
                'label' => 'Beitritt'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Team::class
        ]);
    }
}