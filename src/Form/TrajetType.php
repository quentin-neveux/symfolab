<?php

namespace App\Form;

use App\Entity\Trajet;
use App\Entity\Vehicle;
use App\Form\VehicleType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class TrajetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('villeDepart', TextType::class, [
                'label' => 'Ville de départ',
            ])
            ->add('villeArrivee', TextType::class, [
                'label' => 'Ville d’arrivée',
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label'  => 'Date et heure de départ',
                'widget' => 'single_text',
            ])

            // ✅ TOKENS (1..15) — plus aucun float
            ->add('tokenCost', ChoiceType::class, [
                'label' => 'Coût du trajet (tokens)',
                'choices' => array_combine(
                    array_map(fn($i) => (string) $i, range(1, Trajet::MAX_TOKEN_COST)),
                    range(1, Trajet::MAX_TOKEN_COST)
                ),
                'placeholder' => false,
                'expanded' => false,
                'multiple' => false,
                'constraints' => [
                    new Range(['min' => 1, 'max' => Trajet::MAX_TOKEN_COST]),
                ],
                'help' => 'Entre 1 et 15 tokens (frais plateforme : + ' . Trajet::PLATFORM_FEE_TOKENS . ' tokens à la réservation).',
            ])

            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
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
