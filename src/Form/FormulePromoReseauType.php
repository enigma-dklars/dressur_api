<?php

namespace App\Form;

use App\Entity\FormulePromoReseau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormulePromoReseauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parent', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2'
                ],
            ])
            ->add('idZefame', null, [
                'attr' => [
                    'class' => 'form-select single-select mb-2',
                    'autofocus' => '',
                ],
            ])
            ->add('titre', TextType::class, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])            
            ->add('description', null, [
                'attr' => [
                    'class' => 'form-control mb-2',
                    'rows' => '7'
                ],
            ])
            ->add('prix', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('qte', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('qteMin', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('qteMax', null, [
                'attr' => [
                    'class' => 'form-control mb-2'
                ],
            ])
            ->add('prixZefame', null, [
                'label' => 'Prix Zefame',
                'required' => false,
                'attr' => [
                    'class' => 'form-control mb-2',
                    'step' => '0.00001',
                    'placeholder' => 'Ex : 0.0085'
                ],
            ])
            ->add('prixVendeur', null, [
                'label' => 'Prix Vendeur',
                'required' => false,
                'attr' => [
                    'class' => 'form-control mb-2',
                    'step' => '0.01',
                    'placeholder' => 'Ex : 500'
                ],
            ])
            ->add('available', null, [
                'attr' => [
                    'class' => 'ms-2 mb-2'
                ],
            ])
            ->add('commentairesRequis', CheckboxType::class, [
                'label' => 'Cette formule exige des commentaires personnalisés',
                'required' => false,
                'help' => 'La valeur est utilisée pour valider et transmettre les commentaires, indépendamment du titre du service.',
                'label_attr' => [
                    'class' => 'fw-semibold',
                ],
                'attr' => [
                    'class' => 'ms-2 mb-2',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FormulePromoReseau::class,
        ]);
    }
}
