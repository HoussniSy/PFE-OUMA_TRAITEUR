<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nom de l\'entreprise.']),
                ],
            ])
            ->add('nameArabic', TextType::class, [
                'label' => 'Nom en arabe',
                'required' => false,
            ])
            ->add('registrationNumber', TextType::class, [
                'label' => 'Numéro d\'enregistrement',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le numéro d\'enregistrement.']),
                ],
            ])
            ->add('nif', TextType::class, [
                'label' => 'NIF',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le NIF.']),
                ],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le téléphone.']),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('bankName', TextType::class, [
                'label' => 'Nom de la banque',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nom de la banque.']),
                ],
            ])
            ->add('bankAccount', TextType::class, [
                'label' => 'Numéro de compte bancaire',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le numéro de compte bancaire.']),
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo principal',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG).',
                    ]),
                ],
            ])
            // --- Personnalisation : Thème de couleurs (Feature 27) ---
            ->add('primaryColor', ColorType::class, [
                'label' => 'Couleur principale',
                'help' => 'Couleur principale utilisée dans l\'interface et les documents PDF',
                'attr' => ['class' => 'form-control form-control-color', 'style' => 'width: 80px; height: 40px;'],
            ])
            ->add('colorTheme', ChoiceType::class, [
                'label' => 'Thème prédéfini',
                'required' => false,
                'placeholder' => 'Personnalisé',
                'choices' => [
                    '🟢 Vert (défaut)' => 'green',
                    '🔵 Océan' => 'ocean',
                    '🟠 Coucher de soleil' => 'sunset',
                    '🟣 Violet' => 'purple',
                    '🔴 Rouge' => 'red',
                    '🔵 Bleu royal' => 'royal',
                ],
                'help' => 'Sélectionnez un thème ou choisissez une couleur personnalisée ci-dessus',
            ])
            // --- Personnalisation : Logos par document (Feature 28) ---
            ->add('logoQuoteFile', FileType::class, [
                'label' => 'Logo pour les devis',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG).',
                    ]),
                ],
            ])
            ->add('logoInvoiceFile', FileType::class, [
                'label' => 'Logo pour les factures',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG).',
                    ]),
                ],
            ])
            ->add('quoteWatermark', CheckboxType::class, [
                'label' => 'Ajouter un filigrane "DEVIS" sur les devis PDF',
                'required' => false,
                'help' => 'Un watermark semi-transparent sera ajouté en fond des devis générés',
            ])
            // --- Paramètres par défaut ---
            ->add('defaultTaxRate', NumberType::class, [
                'label' => 'TVA par défaut (%)',
                'scale' => 2,
                'help' => 'Taux de TVA appliqué par défaut aux nouveaux documents',
                'attr' => ['min' => 0, 'max' => 100, 'step' => '0.01', 'placeholder' => '16.00'],
            ])
            ->add('defaultPaymentTerms', ChoiceType::class, [
                'label' => 'Délai de paiement par défaut',
                'choices' => [
                    '30 jours' => 30,
                    '60 jours' => 60,
                    '90 jours' => 90,
                ],
                'help' => 'Délai de paiement appliqué par défaut aux nouvelles factures',
            ])
            ->add('defaultCurrency', ChoiceType::class, [
                'label' => 'Devise par défaut',
                'choices' => [
                    'Ouguiya (MRU)' => 'MRU',
                    'Euro (EUR)' => 'EUR',
                    'Dollar US (USD)' => 'USD',
                ],
                'help' => 'Devise par défaut pour les nouveaux documents',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
