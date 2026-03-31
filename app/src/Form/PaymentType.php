<?php

namespace App\Form;

use App\Entity\Document;
use App\Entity\Payment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('document', EntityType::class, [
                'label' => 'Document',
                'class' => Document::class,
                'choice_label' => 'number',
                'placeholder' => '-- Sélectionnez un document --',
                'required' => false, // Optionnel si passé depuis le contrôleur
                'attr' => ['class' => 'form-select'],
            ])
            ->add('datePaiement', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'data' => new \DateTime(), // Date du jour par défaut
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer la date de paiement.']),
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'MRU',
                'attr' => ['class' => 'form-control', 'min' => 0, 'step' => 0.01],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le montant.']),
                ],
            ])
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Espèces' => 'especes',
                    'Chèque' => 'cheque',
                    'Virement' => 'virement',
                    'Carte bancaire' => 'carte',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un mode de paiement.']),
                ],
            ])
            ->add('statutPaiement', ChoiceType::class, [
                'label' => 'Statut du paiement',
                'attr' => ['class' => 'form-select'],
                'data' => 'recu', // Valeur par défaut "Reçu"
                'choices' => [
                    'En attente' => 'en_attente',
                    'Reçu' => 'recu',
                    'Annulé' => 'annule',
                ],
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: CHQ-12345, VIR-67890'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Informations complémentaires...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
