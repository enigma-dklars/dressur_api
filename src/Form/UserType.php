<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uid')
            ->add('pseudo')
            ->add('nom')
            ->add('mail')
            ->add('pays')
            ->add('tel')
            ->add('apropos')
            ->add('password')
            ->add('createdAt')
            ->add('mailIsVerified')
            ->add('telIsVerified')
            ->add('soldeBonus')
            ->add('codeBonus')
            ->add('themeDark')
            ->add('admin')
            ->add('blocked')
            ->add('tiktok')
            ->add('instagram')
            ->add('facebook')
            ->add('youtube')
            ->add('lang')
            ->add('lastLoginTo')
            ->add('parrain')
            ->remove('preference')
            ->remove('contact')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
