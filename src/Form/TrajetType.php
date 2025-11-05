<?php

namespace App\Form;

use App\Entity\Trajet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
                'attr' => [
                    'placeholder' => 'Ex : Lyon',
                    'class' => 'form-control'
                ],
            ])
            ->add('villeArrivee', TextType::class, [
                'label' => 'Ville d’arrivée',
                'attr' => [
                    'placeholder' => 'Ex : Grenoble',
                    'class' => 'form-control'
                ],
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control rounded-pill px-3 py-2 text-center',
                ],
            ])
            ->add('typeVehicule', TextType::class, [
                'label' => 'Type de véhicule',
                'attr' => [
                    'placeholder' => 'Ex : Berline, SUV…',
                    'class' => 'form-control'
                ],
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix par passager (€)',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => 'Ex : 12.50',
                    'class' => 'form-control'
                ],
            ])
            ->add('placesDisponibles', IntegerType::class, [
                'label' => 'Places disponibles',
                'attr' => [
                    'min' => 1,
                    'max' => 8,
                    'class' => 'form-control'
                ],
            ])
            ->add('energie', ChoiceType::class, [
                'label' => 'Type d’énergie',
                'choices' => [
                    'Essence' => 'Essence',
                    'Diesel' => 'Diesel',
                    'Hybride' => 'Hybride',
                    'Électrique' => 'Électrique',
                ],
                'placeholder' => 'Choisis une énergie',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Infos pratiques, pause café, bagages…',
                    'rows' => 3,
                    'class' => 'form-control'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}
