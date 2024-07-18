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
            ->add('nom')
            ->add('email')
            ->add('numero')
            ->add('adresseMac')
            ->add('uuidMachine')
            ->add('diskSerialNumber')
            ->add('createdAt')
            ->add('expiratedAt')
            ->add('typeMachine')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserBot::class,
        ]);
    }
}
