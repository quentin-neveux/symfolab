<?php

namespace App\Form;

use App\Entity\Trajet;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrajetEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // User (conducteur) reçu via les options
        $user = $options['user'];

        $builder

            // 🕒 Date modifiable
            ->add('dateDepart', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Nouvelle date/heure de départ',
                'required' => true,
            ])

            // 🚗 Sélection d’un véhicule appartenant au conducteur
            ->add('vehicle', EntityType::class, [
                'class' => Vehicle::class,
                'choices' => $user ? $user->getVehicles() : [],
                'choice_label' => fn($v) => $v->getMarque() . ' ' . $v->getModele(),
                'placeholder' => '— Aucun changement —',
                'required' => false, // facultatif
                'label' => 'Véhicule utilisé',
            ])

            // 👥 Places modifiables
            ->add('placesDisponibles', IntegerType::class, [
                'label' => 'Places disponibles',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', ['null', \App\Entity\User::class]);
    }
}
