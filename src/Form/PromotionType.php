<?php

namespace App\Form;

use App\Entity\Promotion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('formulePromoAffaire', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2'
                ],
            ])
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2'
                ],
            ])
            ->add('description', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('image', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('dateDebut', null, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('dateExp', null, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('status', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('nombreDeVue', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('mode', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('nombreImpression', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('motif', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('limited', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            // ->add('whoSaw')
            ->add('isFakeVue', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('referencement', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('inProgrammeRecompense', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('publishOnDressurStatus', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promotion::class,
        ]);
    }
}
