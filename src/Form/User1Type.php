<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class User1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parrain', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2'
                ],
            ])
            ->add('pseudo', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('nom', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('mail', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('pays', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('tel', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('mailIsVerified', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('telIsVerified', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('soldeBonus', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('codeBonus', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('admin', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('blocked', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('tiktok', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('instagram', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('facebook', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('youtube', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('apropos', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            // ->add('uid')
            // ->add('password')
            // ->add('createdAt')
            // ->add('themeDark')
            // ->add('lang')
            // ->add('lastLoginTo')
            // ->add('avatar')
            // ->add('banniere')
            // ->add('hasReceived')
            // ->add('preference')
            // ->add('contact')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
