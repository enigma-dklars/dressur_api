<?php

namespace App\Form;

use App\Entity\Boost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BoostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateDebut', null, [
                'widget'   => 'single_text',
                'attr'     => ['class' => 'form-control mb-2'],
            ])
            ->add('dateExp', null, [
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control mb-2'],
            ])
            ->add('typeBoost', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label'   => 'Type de boost',
                'choices' => ['Par Durée (date)' => 'date', 'Par Contacts (quota)' => 'quota'],
                'attr'    => ['class' => 'form-select mb-2'],
            ])
            ->add('nbContactsObtenus', null, [
                'label' => 'Nb. contacts obtenus',
                'attr'  => ['class' => 'form-control mb-2'],
            ])
            ->add('mode')
            ->add('source', null, [
                'required' => false,
                'attr'     => ['class' => 'form-control mb-2'],
            ])
            ->add('formuleBoost')
            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Boost::class,
        ]);
    }
}
