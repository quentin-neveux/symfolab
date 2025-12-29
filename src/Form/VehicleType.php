<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marque', TextType::class, [
                'label' => 'Marque du véhicule',
            ])

            ->add('modele', TextType::class, [
                'label' => 'Modèle',
            ])

            ->add('immatriculation', TextType::class, [
                'label' => 'Immatriculation',
            ])

            ->add('dateImmatriculation', DateType::class, [
                'label' => 'Date d’immatriculation',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('energie', ChoiceType::class, [
                'label' => 'Énergie',
                'choices' => [
                    'Essence'     => 'Essence',
                    'Diesel'      => 'Diesel',
                    'Hybride'     => 'Hybride',
                    'Électrique'  => 'Électrique',
                ],
                'placeholder' => 'Choisissez une énergie',
            ])

            ->add('couleur', TextType::class, [
                'label' => 'Couleur',
                'required' => false,
            ])

            ->add('type', ChoiceType::class, [
                'label' => 'Type de véhicule',
                'required' => false,
                'choices' => [
                    'Berline'     => 'Berline',
                    'SUV'         => 'SUV',
                    'Citadine'   => 'Citadine',
                    'Break'      => 'Break',
                    'Coupé'      => 'Coupé',
                    'Utilitaire' => 'Utilitaire',
                ],
                'placeholder' => 'Choisissez un type',
            ])

            ->add('places', IntegerType::class, [
                'label' => 'Nombre de places',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}
