<?php

namespace App\Form;

use App\Entity\Trajet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrajetEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDepart', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Nouvelle heure de départ',
                'input' => 'string',
                'mapped' => false,
                'html5' => true,
                'required' => false,
            ])

            ->add('placesDisponibles', NumberType::class, [
                'label' => 'Places disponibles',
            ]);
            // NOTE : plus de champ "prix"
            // NOTE : tokenCost est calculé automatiquement -> pas dans le formulaire
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}
