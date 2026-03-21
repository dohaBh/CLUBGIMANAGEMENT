<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Cellule;
use App\Enum\Niveau;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('nom')
            ->add('prenom')
            ->add('telephone')
            ->add('niveau', EnumType::class, [
                'class' => Niveau::class,
                'choice_label' => fn (Niveau $niveau) => $niveau->value,
                'label' => 'Niveau',
                'placeholder' => 'Choisissez votre niveau',
                'attr' => ['class' => 'form-input'],
            ])
            ->add('cellule', EntityType::class, [
                'class' => Cellule::class,
                'choice_label' => 'nom',
                'label' => 'Cellule',
                'placeholder' => 'Choisissez une cellule',
                'required' => true,
                'attr' => ['class' => 'form-input'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }
// mapped c'est elle qui stocke les données dans la base de données 
//donc lorsqu'on met false cela ne se stocke pas mais on le récupère au controller pour le hasher ?


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}