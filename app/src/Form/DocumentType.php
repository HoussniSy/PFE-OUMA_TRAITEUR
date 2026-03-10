<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Document;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('number', TextType::class, [
                'label' => 'Numéro',
                'disabled' => true,
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('client', EntityType::class, [
                'label' => 'Client',
                'class' => Client::class,
                'choice_label' => 'name',
                'placeholder' => '-- Sélectionnez un client --',
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('taxRate', NumberType::class, [
                'label' => 'Taux TVA (%)',
                'scale' => 2,
                'help' => 'Taux de TVA pour ce document',
                'attr' => ['min' => 0, 'max' => 100, 'step' => '0.01'],
            ])
            ->add('items', CollectionType::class, [
                'label' => 'Prestations',
                'entry_type' => DocumentItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'attr' => [
                    'class' => 'document-items-collection',
                ],
            ])
            ->add('paymentTerms', ChoiceType::class, [
                'label' => 'Délai de paiement',
                'choices' => [
                    '30 jours' => 30,
                    '60 jours' => 60,
                    '90 jours' => 90,
                ],
                'help' => 'Délai de paiement pour la facture',
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
