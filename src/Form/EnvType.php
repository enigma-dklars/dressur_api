<?php

namespace App\Form;

use App\Entity\Env;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('commissionBonus', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('versionApp', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('importantUpdate', null, [
                'attr' => [
                    'class' => 'ms-2 mb-3 mt-3'
                ],
            ])
            ->add('doBoostPayant', null, [
                'attr' => [
                    'class' => 'ms-2 mb-3 mt-3'
                ],
            ])
            ->add('linkLocalServer', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->remove('usersTel')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Env::class,
        ]);
    }
}
