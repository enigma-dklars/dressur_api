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
            ->add('user', null, [
                'attr' => [
                    'class' => 'form-select mb-2'
                ],
            ])
            ->add('formuleCampagneMail', null, [
                'attr' => [
                    'class' => 'form-select mb-2'
                ],
            ])
            ->add('titre', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('sujet', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('replyto', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('sendto', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('contentmail', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('createdAt', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('status', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('motif', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CampagneMail::class,
        ]);
    }
}
