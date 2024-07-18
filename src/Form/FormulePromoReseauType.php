<?php

namespace App\Form;

use App\Entity\FormulePromoReseau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormulePromoReseauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('iconFlutterName')
            ->add('description')
            ->add('descriptionEn')
            ->add('prix')
            ->add('qte')
            ->add('qteMin')
            ->add('qteMax')
            ->add('available')
            ->add('parent')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormulePromoReseau::class,
        ]);
    }
}
