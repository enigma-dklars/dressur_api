<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- Identité ---
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('mail', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('tel', TextType::class, [
                'label' => 'Téléphone (format international)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '+22890000000'],
            ])
            ->add('pays', IntegerType::class, [
                'label' => 'Pays (ID)',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('lang', TextType::class, [
                'label' => 'Langue (fr / en)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'maxlength' => 2],
            ])
            ->add('apropos', TextareaType::class, [
                'label' => 'À propos',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])

            // --- Statuts & droits ---
            ->add('mailIsVerified', CheckboxType::class, [
                'label' => 'Email vérifié',
                'required' => false,
                'attr' => ['class' => 'form-check-input ms-2'],
                'label_attr' => ['class' => 'form-check-label ms-1'],
            ])
            ->add('telIsVerified', CheckboxType::class, [
                'label' => 'Téléphone vérifié',
                'required' => false,
                'attr' => ['class' => 'form-check-input ms-2'],
                'label_attr' => ['class' => 'form-check-label ms-1'],
            ])
            ->add('admin', CheckboxType::class, [
                'label' => 'Administrateur',
                'required' => false,
                'attr' => ['class' => 'form-check-input ms-2'],
                'label_attr' => ['class' => 'form-check-label ms-1'],
            ])
            ->add('lecteur', null, [
                'attr' => ['class' => 'ms-2 mb-2'],
                'required' => false,
            ])
            ->add('blocked', CheckboxType::class, [
                'label' => 'Bloqué',
                'required' => false,
                'attr' => ['class' => 'form-check-input ms-2'],
                'label_attr' => ['class' => 'form-check-label ms-1'],
            ])
            ->add('vendeur', CheckboxType::class, [
                'label' => 'Vendeur',
                'required' => false,
                'attr' => ['class' => 'form-check-input ms-2'],
                'label_attr' => ['class' => 'form-check-label ms-1'],
            ])
            ->add('estPartenaire', CheckboxType::class, [
                'label' => 'Est Partenaire',
                'required' => false,
                'attr' => ['class' => 'form-check-input ms-2'],
                'label_attr' => ['class' => 'form-check-label ms-1'],
            ])

            // --- Programme de récompenses ---
            ->add('isInscritProgrammeRecompense', CheckboxType::class, [
                'label' => 'Inscrit au programme de récompenses',
                'required' => false,
                'attr' => ['class' => 'form-check-input ms-2'],
                'label_attr' => ['class' => 'form-check-label ms-1'],
            ])
            ->add('soldeDressur', IntegerType::class, [
                'label' => 'Solde programme de récompenses (FCFA)',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            // --- Identifiants techniques ---
            ->add('uid', TextType::class, [
                'label' => 'UID',
                'disabled' => true,
                'attr' => ['class' => 'form-control', 'readonly' => true],
            ])
            ->add('lid', TextType::class, [
                'label' => 'LID (WhatsApp)',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('codePartenaire', TextType::class, [
                'label' => 'Code Partenaire',
                'required' => false,
                'attr' => ['class' => 'form-control', 'maxlength' => 8],
            ])
            ->add('registerSource', TextType::class, [
                'label' => 'Source d\'inscription',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('lastLoginSource', TextType::class, [
                'label' => 'Source dernière connexion',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])

            // --- Dates ---
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Date de création',
                'required' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('lastLoginTo', DateTimeType::class, [
                'label' => 'Dernière connexion',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])

            // --- Mot de passe (laisser vide pour ne pas modifier) ---
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe (laisser vide pour ne pas modifier)',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
