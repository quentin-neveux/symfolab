<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Ex : Jean',
                    'class' => 'form-control rounded-pill px-3 py-2'
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Ex : Dupont',
                    'class' => 'form-control rounded-pill px-3 py-2'
                ],
            ])
            ->add('email', RepeatedType::class, [
                'type' => EmailType::class,
                'first_options'  => [
                    'label' => 'Adresse e-mail',
                    'attr' => [
                        'placeholder' => 'exemple@domaine.fr',
                        'class' => 'form-control rounded-pill px-3 py-2'
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation e-mail',
                    'attr' => [
                        'placeholder' => 'Répétez votre adresse e-mail',
                        'class' => 'form-control rounded-pill px-3 py-2'
                    ],
                ],
                'invalid_message' => 'Les adresses e-mail doivent correspondre.',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'placeholder' => '••••••••',
                        'class' => 'form-control rounded-pill px-3 py-2'
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation mot de passe',
                    'attr' => [
                        'placeholder' => 'Répétez votre mot de passe',
                        'class' => 'form-control rounded-pill px-3 py-2'
                    ],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => "J’accepte les conditions d’utilisation",
                'label_attr' => ['class' => 'form-check-label ms-2'],
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [
                    new IsTrue(['message' => 'Vous devez accepter les conditions pour continuer.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
