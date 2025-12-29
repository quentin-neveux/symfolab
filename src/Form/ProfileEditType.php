<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --------------------------------------------------
            // Informations personnelles
            // --------------------------------------------------
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Parlez un peu de vous...',
                    'rows' => 4,
                ],
            ])

            // --------------------------------------------------
            // Photo de profil (non mappée)
            // --------------------------------------------------
            ->add('photo', FileType::class, [
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Format invalide (JPG, PNG ou WebP uniquement)',
                    ])
                ],
            ])


            // --------------------------------------------------
            // Préférences de voyage
            // --------------------------------------------------
            ->add('musique', ChoiceType::class, [
                'label' => 'Musique',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Indifférent' => 'indifferent',
                ],
            ])
            ->add('discussion', ChoiceType::class, [
                'label' => 'Discussion',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Indifférent' => 'indifferent',
                ],
            ])
            ->add('animaux', ChoiceType::class, [
                'label' => 'Animaux',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Indifférent' => 'indifferent',
                ],
            ])
            ->add('pausesCafe', ChoiceType::class, [
                'label' => 'Pauses café',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Indifférent' => 'indifferent',
                ],
            ])
            ->add('fumeur', ChoiceType::class, [
                'label' => 'Fumeur',
                'choices' => [
                    'Oui' => 'oui',
                    'Non' => 'non',
                    'Indifférent' => 'indifferent',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
