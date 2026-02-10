<?php

namespace App\Form;

use App\Entity\HistoriqueProgrammeRecompense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class HistoriqueProgrammeRecompenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nbrVue', null, [
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
            ])
            ->add('nbrPartage', null, [
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
            ])
            ->add('recompense', null, [
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
            ])
            ->add('status', null, [
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
            ])
            ->add('referenceParticipation', null, [
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
            ])
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
            ])
            ->add('updatedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2',
                ],
            ])
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5',
                ],
            ])
            ->add('promotion', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoriqueProgrammeRecompense::class,
        ]);
    }
}
