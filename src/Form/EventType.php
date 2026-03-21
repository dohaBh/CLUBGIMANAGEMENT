<?php

namespace App\Form;

use App\Entity\Event;
use App\Enum\StatutFormation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => ['class' => 'form-control']
            ])
            ->add('theme', TextType::class, [
                'label' => 'Thème',
                'attr' => ['class' => 'form-control']
            ])
            ->add('enCollab', CheckboxType::class, [
                'label' => 'En collaboration ?',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('partenaire', TextType::class, [
                'label' => 'Partenaire',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('programme', TextareaType::class, [
                'label' => 'Programme',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['class' => 'form-control']
            ])
            ->add('statutEvent', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => StatutFormation::cases(), 
                'choice_label' => function (StatutFormation $statut) {
                    return $statut->value;  
                },
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}