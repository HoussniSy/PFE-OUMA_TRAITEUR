<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserEmailSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('smtpHost', TextType::class, [
                'label' => 'Serveur SMTP',
                'required' => true,
                'attr' => [
                    'placeholder' => 'smtp.gmail.com',
                    'class' => 'form-control',
                ],
            ])
            ->add('smtpPort', IntegerType::class, [
                'label' => 'Port',
                'required' => true,
                'attr' => [
                    'placeholder' => '587',
                    'class' => 'form-control',
                ],
            ])
            ->add('smtpUsername', TextType::class, [
                'label' => 'Nom d\'utilisateur (email)',
                'required' => true,
                'attr' => [
                    'placeholder' => 'votre@email.com',
                    'class' => 'form-control',
                ],
            ])
            ->add('smtpPassword', PasswordType::class, [
                'label' => 'Mot de passe / Mot de passe d\'application',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Laisser vide pour ne pas modifier',
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                ],
            ])
            ->add('smtpEncryption', ChoiceType::class, [
                'label' => 'Chiffrement',
                'choices' => [
                    'TLS' => 'tls',
                    'SSL' => 'ssl',
                    'Aucun' => 'none',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
