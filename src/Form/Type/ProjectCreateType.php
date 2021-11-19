<?php

namespace jjansen\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use jjansen\Entity\Project;
use jjansen\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ProjectCreateType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder
            ->add('name', TextType::class, [
                'label' => false,
                'attr' => [
                    'aria-describedby' => 'btn-project-label',
                    'placeholder' => "Projekt-Name..."
                ],
                'row_attr' => ['class' => "d"],
            ])
            ->add('quickid', TextType::class, [
                'label' => "Kürzel",
                'attr' => [
                    'placeholder' => "#12345AbCDe"
                ],
                'required' => false
            ])
            ->add('description', TextareaType::class, [
                'label' => "Beschreibung",
                'required' => false
            ])
            ->add('visibility', ChoiceType::class, [
                'choices' => [
                    'Öffentlich' => 1,
                    'Privat' => 0
                ],
                'label' => "Sichtbarkeit"

            ])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => "name",
                'label' => "Zum Team hinzufügen",
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('t')
                        ->where('t.admin = :admin')
                        ->setParameter('admin', $this->security->getUser()->getId());
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class
        ]);
    }
}