<?php

namespace App\Form;

use App\Entity\PromoReseau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromoReseauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select mb-2'
                ],
            ])
            ->add('formulePromoReseau', null, [
                'attr' => [
                    'class' => 'form-select mb-2'
                ],
            ])
            ->add('status', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('idZefame', null, [
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
            ->add('qteDemander', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('prixFixer', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('url', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('createdAt', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('updatedAt', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
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
