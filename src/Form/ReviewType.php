<?php

namespace App\Form;

use App\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', HiddenType::class)
            ->add('comment', TextareaType::class, [
                'label' => 'Ton commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rounded-4 p-3',
                    'placeholder' => 'Partage ton expérience…'
                ]
            ])
            ->add('tags', HiddenType::class, [
                "mapped" => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Review::class]);
    }
}
