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
            ->add('idTransaction')
            ->add('reference')
            ->add('amount')
            ->add('status')
            ->add('customerId')
            ->add('currencyId')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('annotherInfo')
            ->add('transactionFor')
            ->add('user')
            ->add('userBot')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
