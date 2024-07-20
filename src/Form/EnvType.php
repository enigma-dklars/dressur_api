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
            ->add('commissionBonus')
            ->add('versionApp')
            ->add('importantUpdate')
            ->add('usersTel')
            ->add('doBoostPayant')
            ->add('linkLocalServer')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Env::class,
        ]);
    }
}
