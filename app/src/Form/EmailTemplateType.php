<?php

namespace App\Form;

use App\Entity\EmailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EmailTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code unique',
                'attr' => [
                    'placeholder' => 'ex: quote_send, invoice_reminder',
                    'class' => 'form-control',
                ],
                'help' => 'Code unique pour identifier le template (lettres, chiffres et underscore uniquement)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9_]+$/',
                        'message' => 'Le code ne peut contenir que des lettres minuscules, chiffres et underscores',
                    ]),
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Le code ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom du template',
                'attr' => [
                    'placeholder' => 'ex: Envoi de devis',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Documents' => 'document',
                    'Utilisateurs' => 'user',
                    'Paiements' => 'payment',
                    'Relances' => 'reminder',
                    'Autre' => 'other',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La catégorie est obligatoire']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description du template et de son utilisation',
                    'class' => 'form-control',
                    'rows' => 3,
                ],
                'help' => 'Optionnel : décrivez à quoi sert ce template',
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet de l\'email',
                'attr' => [
                    'placeholder' => 'ex: Votre {{type}} n°{{numero}} - {{nom_entreprise}}',
                    'class' => 'form-control',
                ],
                'help' => 'Vous pouvez utiliser des variables comme {{nom_client}}, {{numero}}, etc.',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le sujet est obligatoire']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le sujet ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Contenu HTML',
                'attr' => [
                    'class' => 'form-control tinymce-editor',
                    'rows' => 20,
                ],
                'help' => 'Contenu HTML de l\'email. Utilisez l\'éditeur visuel ci-dessous.',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le contenu est obligatoire']),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Template actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Seuls les templates actifs peuvent être utilisés',
            ])
        ;

        // Ne pas permettre de modifier le code et isDefault pour les templates par défaut
        if ($options['is_default']) {
            $builder->get('code')->setDisabled(true);

            $builder->add('isDefault', CheckboxType::class, [
                'label' => 'Template par défaut du système',
                'disabled' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Les templates par défaut ne peuvent pas être supprimés',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplate::class,
            'is_default' => false,
        ]);
    }
}
