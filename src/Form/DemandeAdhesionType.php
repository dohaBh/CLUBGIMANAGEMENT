<?php

namespace App\Form;

use App\Entity\DemandeAdhesion;
use App\Entity\Cellule;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeAdhesionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cellule', EntityType::class, [
                'class' => Cellule::class,
                'choice_label' => 'nom',
                'label' => 'Cellule souhaitée',
                'placeholder' => 'Sélectionnez une cellule',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('motivation', TextareaType::class, [
                'label' => 'Motivation',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Expliquez pourquoi vous souhaitez rejoindre cette cellule...'
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeAdhesion::class,
        ]);
    }
}