<?php

namespace App\Form;

use App\Entity\FormulePromoReseau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormulePromoReseauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parent', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2'
                ],
            ])
            ->add('idZefame', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2',
                    'autofocus' => '',
                ],
            ])
            ->add('titre', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('description', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('descriptionEn', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('prix', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('qte', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('qteMin', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('qteMax', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('iconFlutterName', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('available', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormulePromoReseau::class,
        ]);
    }
}
