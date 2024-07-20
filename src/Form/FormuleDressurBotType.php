<?php

namespace App\Form;

use App\Entity\FormuleDressurBot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormuleDressurBotType extends AbstractType
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
            ->add('nbrJour', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('signature', null, [
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
                'label' => "Signature oui ou non"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormuleDressurBot::class,
        ]);
    }
}
