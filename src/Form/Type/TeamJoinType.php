<?php

namespace jjansen\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamJoinType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Team-Name*',
                'required' => true
            ])
            ->add('quickid', TextType::class, [
                'label' => 'Kürzel',
                'help' => '* Felder sind Pflichtfelder',
                'attr' => [
                    'placeholder' => '#12345abcde'
                ],
                'required' => false
            ]);
    }

}