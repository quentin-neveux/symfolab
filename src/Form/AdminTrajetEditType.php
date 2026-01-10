<?php

namespace App\Form;

use App\Entity\Trajet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminTrajetEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('villeDepart', TextType::class, [
                'label' => 'Lieu de départ',
            ])
            ->add('villeArrivee', TextType::class, [
                'label' => 'Lieu d\'arrivée',
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => 'Heure de départ',
                'widget' => 'single_text',
            ])
            ->add('dateArrivee', DateTimeType::class, [
                'label' => 'Heure d\'arrivée',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}