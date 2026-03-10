<?php

namespace App\Form;

use App\Entity\Client;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class WhatsAppMessageType extends AbstractType
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
                ],
            ])
            ->add('recipientPhone', TextType::class, [
                'label' => 'Numéro WhatsApp',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+222 XX XX XX XX',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le numéro WhatsApp est obligatoire.'),
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
                'required' => !$options['is_document'],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => $options['is_document'] ? 'Légende du document (optionnel)...' : 'Votre message...',
                ],
                'constraints' => $options['is_document'] ? [] : [
                    new Assert\NotBlank(message: 'Le message est obligatoire.'),
                ],
            ]);

        if ($options['is_document']) {
            $builder->add('document', FileType::class, [
                'label' => 'Document à envoyer',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le document est obligatoire.'),
                    new Assert\File(
                        maxSize: '16M',
                        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). Taille maximale: {{ limit }} {{ suffix }}.',
                        mimeTypes: [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'image/jpeg',
                            'image/png',
                        ],
                        mimeTypesMessage: 'Format de fichier non supporté.'
                    ),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_document' => false,
        ]);
    }
}
