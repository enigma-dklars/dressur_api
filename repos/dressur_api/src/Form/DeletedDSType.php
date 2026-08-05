<?php

namespace App\Form;

use App\Entity\DeletedDS;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeletedDSType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif')
            ->add('createdAt', null, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('tel')
            ->add('mail')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeletedDS::class,
        ]);
    }
}
