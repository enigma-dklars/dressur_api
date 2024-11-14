<?php

namespace App\Form;

use App\Entity\EnvMailSender;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvMailSenderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mailAdresse', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('countMailSent', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('activated', null, [
                'attr' => [
                    'class' => 'ms-2 mb-3 mt-3'
                ],
            ])
            ->add('password', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('smtpServer', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('smtpPort', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('smtpSecured', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EnvMailSender::class,
        ]);
    }
}
