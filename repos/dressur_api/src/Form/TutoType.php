<?php

namespace App\Form;

use App\Entity\Tuto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TutoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('url', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('description', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('activated', null, [
                'attr' => [
                    'class' => 'mx-2'
                ],
            ])
            ->remove('createdAt')
            ->remove('updatedAt')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tuto::class,
        ]);
    }
}
