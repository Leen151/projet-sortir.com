<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModifMotPasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ancien_motPasse', PasswordType::class, [
                'label'=> 'Mot de passe actuel',
                'mapped' => false,
                'required' => true,
            ])

            ->add('nouveau_motPasse', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message'=> "Les 2 champs doivent être identiques",
                'first_options'  => ['label' => 'Nouveau mot de passe'],
                'second_options' => ['label' => 'Confirmez la saisie'],
                'required' => true,
                'error_bubbling' => true
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
