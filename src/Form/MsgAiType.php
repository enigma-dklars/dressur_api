<?php

namespace App\Form;

use App\Entity\MsgAi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MsgAiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recepteur', null, [
                'attr' => [
                    'class' => 'form-control mb-3'
                ],
            ])
            ->add('message', null, [
                'attr' => [
                    'class' => 'form-control mb-3',
                    'rows' => '10',
                ],
            ])
            ->remove('createdAt')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MsgAi::class,
        ]);
    }
}
