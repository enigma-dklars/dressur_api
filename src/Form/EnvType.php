<?php

namespace App\Form;

use App\Entity\Env;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class EnvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('versionApp', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('importantUpdate', null, [
                'attr' => [
                    'class' => 'ms-2 mb-3 mt-3'
                ],
            ])
            ->add('versionDressurBot', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('iaActive', null, [
                'label' => '🤖 Assistant IA actif',
                'attr'  => [
                    'class' => 'ms-2 mb-3 mt-3'
                ],
                'label_attr' => [
                    'class' => 'fw-semibold'
                ],
            ])
            ->add('fraisAdhesionVendeur', IntegerType::class, [
                'label' => 'Frais d’adhésion vendeur (FCFA)',
                'help' => 'Droit d’adhésion payé à Dressur, non crédité sur le solde utilisateur.',
                'html5' => true,
                'constraints' => [
                    new GreaterThanOrEqual(0),
                ],
                'attr' => [
                    'class' => 'form-control mb-2',
                    'min' => 0,
                ],
            ])
            ->add('montantRechargeInitialeDeveloppeur', IntegerType::class, [
                'label' => 'Recharge initiale développeur (FCFA)',
                'help' => 'Montant requis pour l’activation développeur et crédité intégralement au solde Dressur.',
                'html5' => true,
                'constraints' => [
                    new GreaterThanOrEqual(0),
                ],
                'attr' => [
                    'class' => 'form-control mb-2',
                    'min' => 0,
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Env::class,
        ]);
    }
}
