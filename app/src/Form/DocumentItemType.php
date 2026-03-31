<?php

namespace App\Form;

use App\Entity\DocumentItem;
use App\Entity\ServiceCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class DocumentItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('designation', TextType::class, [
                'label' => 'Désignation',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une désignation.']),
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => ServiceCategory::class,
                'choice_label' => 'name',
                'placeholder' => '-- Catégorie (optionnel) --',
                'required' => false,
            ])
            ->add('numberOfDays', IntegerType::class, [
                'label' => 'Nombre de jours',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nombre de jours.']),
                    new GreaterThanOrEqual(['value' => 1]),
                ],
            ])
            ->add('numberOfPersons', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nombre de personnes.']),
                    new GreaterThanOrEqual(['value' => 1]),
                ],
            ])
            ->add('numberOfServices', IntegerType::class, [
                'label' => 'Nombre de services',
                'data' => 1,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nombre de services.']),
                    new GreaterThanOrEqual(['value' => 1]),
                ],
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Prix unitaire',
                'currency' => 'MRU',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le prix unitaire.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentItem::class,
        ]);
    }
}
