<?php

namespace App\Form;

use App\Entity\PromoReseau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromoReseauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5'
                ],
            ])
            ->add('formulePromoReseau', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5'
                ],
            ])
            ->add('qteDemander', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('url', TextType::class, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('commentaires', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaires (une ligne par commentaire)',
                'attr' => [
                    'class' => 'form-control mb-2',
                    'rows' => 6,
                ],
            ])
            ->add('idZefame', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('status', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('compteurDebut', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('compteurRestant', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('prixFixer', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('createdAt', null, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('prixZefame', null, [
                'label'    => 'Prix Zefame',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control mb-2',
                    'step'        => '0.00001',
                    'placeholder' => 'Ex : 0.0085',
                ],
            ])
            ->add('source', null, [
                'required' => false,
                'attr'     => ['class' => 'form-control mb-2'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromoReseau::class,
        ]);
    }
}
