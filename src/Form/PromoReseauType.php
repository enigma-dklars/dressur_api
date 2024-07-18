<?php

namespace App\Form;

use App\Entity\PromoReseau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromoReseauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('qteDemander')
            ->add('prixFixer')
            ->add('url')
            ->add('status')
            ->add('idZefame')
            ->add('compteurDebut')
            ->add('compteurRestant')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('user')
            ->add('formulePromoReseau')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PromoReseau::class,
        ]);
    }
}
