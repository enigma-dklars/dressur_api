<?php

namespace App\Form;

use App\Entity\MethodePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MethodePaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('aggregator', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('pays', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('titre', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('code', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('activated', null, [
                'attr' => [
                    'class' => 'mx-3 mb-2'
                ],
            ])
            ->add('isdirect', null, [
                'attr' => [
                    'class' => 'mx-3 mb-2'
                ],
            ])
            ->add('requires', TextType::class, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('autreMethodeUn', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MethodePaiement::class,
        ]);
    }
}
