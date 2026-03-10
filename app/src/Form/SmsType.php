<?php

namespace App\Form;

use App\Entity\Client;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'label' => 'Client',
                'placeholder' => '-- Sélectionner un client --',
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.phone IS NOT NULL')
                        ->andWhere('c.phone != :empty')
                        ->setParameter('empty', '')
                        ->orderBy('c.name', 'ASC');
                },
                'choice_label' => function (Client $client) {
                    return $client->getName() . ' (' . $client->getPhone() . ')';
                },
                'attr' => [
                    'class' => 'form-select',
                    'data-action' => 'change->sms-form#onClientChange',
                ],
            ])
            ->add('recipientPhone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+222 XX XX XX XX',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.'),
                    new Assert\Length(max: 50, maxMessage: 'Le numéro ne peut pas dépasser {{ limit }} caractères.'),
                ],
            ])
            ->add('recipientName', TextType::class, [
                'label' => 'Nom du destinataire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom du destinataire',
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Votre message...',
                    'maxlength' => 1600,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le message est obligatoire.'),
                    new Assert\Length(max: 1600, maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères (10 segments SMS).'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
