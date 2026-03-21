<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\User;
use App\Enum\StatutFormation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la formation',
                'attr' => ['placeholder' => 'Ex: Formation Symfony Avancé']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 4, 'placeholder' => 'Description détaillée de la formation...']
            ])
            ->add('module', TextType::class, [
                'label' => 'Module',
                'attr' => ['placeholder' => 'Ex: Doctrine, Security, Forms...']
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de la formation',
                'html5' => true,
                'attr' => ['class' => 'datepicker']
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['placeholder' => 'Ex: Salle A, En ligne, Bureau 203...']
            ])
            ->add('nbParticipants', IntegerType::class, [
                'label' => 'Nombre maximum de participants',
                'required' => false,
                'attr' => ['min' => 0, 'placeholder' => '0 pour illimité'],
                'help' => 'Laissez 0 pour aucune limite'
            ])
            ->add('statutFormation', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Planifiée' => StatutFormation::PLANIFIEE,
                    'En cours' => StatutFormation::EN_COURS,
                    'Terminée' => StatutFormation::TERMINEE,
                    'Annulée' => StatutFormation::ANNULEE,
                ],
                'placeholder' => 'Sélectionnez un statut'
            ])
            ->add('formateurs', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->orderBy('u.nom', 'ASC')
                        ->addOrderBy('u.prenom', 'ASC');
                },
                'multiple' => true,
                'required' => false,
                'label' => 'Formateurs',
                'help' => 'Sélectionnez jusqu\'à 6 formateurs maximum parmi les utilisateurs',
                'attr' => [
                    'class' => 'select2-multiple',
                    'data-maximum-selection-length' => 6
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}