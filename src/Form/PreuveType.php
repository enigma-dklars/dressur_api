<?php

namespace App\Form;

use App\Entity\Preuve;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class PreuveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('captureListeStatut', TextType::class, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('captureStatutOuvert', TextType::class, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('updatedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5'
                ],
            ])
            ->add('historiqueProgrammeRecompense', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-5'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Preuve::class,
        ]);
    }
}
