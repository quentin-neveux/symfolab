<?php

namespace App\Form;

use App\Entity\Trajet;
use App\Entity\Vehicle;
use App\Form\VehicleType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrajetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('villeDepart')
            ->add('villeArrivee')
            ->add('dateDepart', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('price')
            ->add('commentaire', TextareaType::class, [
                'required' => false,
            ])

            // ----------------------------------------------------------
            // 🚗 VÉHICULE EXISTANT
            // ----------------------------------------------------------
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choice_label' => fn (Vehicle $v) =>
                    $v->getMarque() . ' ' . $v->getModele() . ' (' . $v->getImmatriculation() . ')',
                'choices' => $user ? $user->getVehicles() : [],
                'placeholder' => 'Sélectionner un véhicule existant',
                'label' => 'Véhicule',
                'required' => false,
            ])

            // ----------------------------------------------------------
            // ➕ NOUVEAU VÉHICULE (OPTIONNEL)
            // ----------------------------------------------------------
            ->add('newVehicle', VehicleType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
            ])

            // ----------------------------------------------------------
            // 👥 PLACES DISPONIBLES
            // ----------------------------------------------------------
            ->add('placesDisponibles', ChoiceType::class, [
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Places disponibles',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
            'user' => null,
        ]);
    }
}
