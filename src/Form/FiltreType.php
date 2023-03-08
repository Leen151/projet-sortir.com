<?php

namespace App\Form;

use App\data\FiltresSorties;
use App\Entity\Campus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FiltreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('campus', EntityType::class, [
                'class'=> Campus::class,
                'choice_label'=> "nom",
                'required' => false,
            ])

            ->add('motClef', TextType::class, [
                'label' => 'Mot clef',
                'required' => false,
                'attr' => [
                    'placeholder' => "Recherche"
                ]
            ])
            ->add('dateMin', DateType::class, [
                'label'=>'entre le ',
                'required' => false,
                'html5'=>true,
                'widget' => 'single_text',
            ])
            ->add('dateMax', DateType::class, [
                'label'=>' et le ',
                'required' => false,
                'html5'=>true,
                'widget' => 'single_text',
            ])

            ->add('participantOrganisateur', CheckboxType::class, [
                'required' => false,
                'label'=> "Sorties dont je suis l'organisateur/trice"
            ])
            ->add('participantInscrit', CheckboxType::class, [
                'required' => false,
                'label'=> "Sorties auxquelles je suis inscrit/e"
            ])
            ->add('participantNonInscrit', CheckboxType::class, [
                'required' => false,
                'label'=> "Sorties auxquelles je ne suis pas inscrit/e"
            ])
            ->add('sortiePassee', CheckboxType::class, [
                'required' => false,
                'label'=> "Sorties passées"
            ])
            ->add('btnFiltrer', SubmitType::class, [
                'label'=>'Rechercher'
            ])
        ;
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FiltresSorties::class,
            'method' => 'GET', //la recherche peut être partagée dans l'url

        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}