<?php

namespace App\Form;

use App\Entity\EnvPaiementApi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvPaiementApiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        
            ->add('apiKey', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('endpointSecret', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('routeWebhook', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('activated', null, [
                'attr' => [
                    'class' => 'ms-2 mb-3 mt-3'
                ],
            ])
            ->add('aggregator', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('environment', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('countTransactionApproved', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EnvPaiementApi::class,
        ]);
    }
}
