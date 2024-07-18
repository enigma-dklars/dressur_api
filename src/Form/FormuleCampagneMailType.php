<?php

namespace App\Form;

use App\Entity\FormuleCampagneMail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormuleCampagneMailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('prix', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('nombre_mail', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormuleCampagneMail::class,
        ]);
    }
}
