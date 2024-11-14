<?php

namespace App\Form;

use App\Entity\Transaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idTransaction', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('reference', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('amount', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('status', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('customerId', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('currencyId', null, [
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
            ->add('annotherInfo', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('transactionFor', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5'
                ],
            ])
            ->add('userBot', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
