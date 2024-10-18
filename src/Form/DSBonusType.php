<?php

namespace App\Form;

use App\Entity\DSBonus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DSBonusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2'
                ],
            ])
            ->add('code', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('montant', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('dateExp', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DSBonus::class,
        ]);
    }
}
