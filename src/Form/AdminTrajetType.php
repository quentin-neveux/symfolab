<?php

namespace App\Form;

use App\Entity\Trajet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminTrajetType extends AbstractType
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
                'label'  => 'Date et heure de départ',
                'widget' => 'single_text',
            ])
            ->add('placesDisponibles', IntegerType::class, [
                'label' => 'Places disponibles',
            ])
            ->add('price', MoneyType::class, [
                'label'    => 'Prix (EcoCrédits ECR)',
                'currency' => 'ECR',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}
