<?php

namespace jjansen\Form\Type;

use jjansen\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MemberCreateType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('uuid', TextType::class, [
                'label' => 'Benutzername*',
                'required' => true
            ])
            ->add('name', TextType::class, [
                'label' => 'Vorname',
                'required' => false,
                'empty_data' => ' '
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nachname',
                'required' => false,
                'empty_data' => ' '
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}