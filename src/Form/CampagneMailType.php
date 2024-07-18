<?php

namespace App\Form;

use App\Entity\CampagneMail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampagneMailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('sujet')
            ->add('replyto')
            ->add('sendto')
            ->add('contentmail')
            ->add('createdAt')
            ->add('status')
            ->add('motif')
            ->add('user')
            ->add('formuleCampagneMail')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CampagneMail::class,
        ]);
    }
}
