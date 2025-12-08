<?php

namespace App\Form;

use App\Entity\Trajet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrajetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('villeDepart', TextType::class, [
                'label' => 'Ville de départ',
            ])
            ->add('villeArrivee', TextType::class, [
                'label' => 'Ville d’arrivée',
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
            ])
            ->add('typeVehicule', TextType::class, [
                'label' => 'Type de véhicule',
                'required' => false,
            ])
            ->add('placesDisponibles', IntegerType::class, [
                'label' => 'Places disponibles',
                'attr' => ['min' => 1, 'max' => 4],
            ])
            ->add('energie', ChoiceType::class, [
                'label' => 'Énergie',
                'choices' => [
                    'Essence' => 'Essence',
                    'Diesel' => 'Diesel',
                    'Hybride' => 'Hybride',
                    'Électrique' => 'Électrique',
                ],
                'placeholder' => 'Choisis une énergie',
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
            ]);
        // tokenCost → calcul automatique dans le controller, donc pas dans le form
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}
