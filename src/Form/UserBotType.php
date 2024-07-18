<?php

namespace App\Form;

use App\Entity\UserBot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserBotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('email', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('numero', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('adresseMac', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('uuidMachine', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('diskSerialNumber', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('createdAt', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('expiratedAt', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

            ->add('typeMachine', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserBot::class,
        ]);
    }
}
