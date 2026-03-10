<?php

namespace App\Form;

use App\Entity\StockItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'article',
                'attr' => ['placeholder' => 'Ex: Riz, Huile, Poulet...'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Description optionnelle...'],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Unité',
                'choices' => [
                    'Kilogramme (kg)' => StockItem::UNIT_KG,
                    'Litre (L)' => StockItem::UNIT_LITRE,
                    'Pièce (pcs)' => StockItem::UNIT_PIECE,
                    'Pack' => StockItem::UNIT_PACK,
                    'Boîte' => StockItem::UNIT_BOITE,
                ],
            ])
            ->add('currentQuantity', NumberType::class, [
                'label' => 'Quantité actuelle',
                'scale' => 2,
                'attr' => ['min' => 0, 'step' => '0.01'],
            ])
            ->add('minimumQuantity', NumberType::class, [
                'label' => 'Quantité minimale (alerte)',
                'scale' => 2,
                'help' => 'Vous serez alerté quand le stock descend en dessous de cette quantité',
                'attr' => ['min' => 0, 'step' => '0.01'],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Prix unitaire (MRU)',
                'required' => false,
                'scale' => 2,
                'attr' => ['min' => 0, 'step' => '0.01', 'placeholder' => 'Optionnel'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StockItem::class,
        ]);
    }
}
