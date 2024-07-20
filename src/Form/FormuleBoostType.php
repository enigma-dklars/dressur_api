<?php

namespace App\Form;

use App\Entity\FormuleBoost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormuleBoostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('prix', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('nbrJour', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('alert', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormuleBoost::class,
        ]);
    }
}
