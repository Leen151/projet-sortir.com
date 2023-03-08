<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class,[
                'label'=> 'Nom de la sortie :'
            ])
            ->add('dateHeureDebut', null,[
                "label"=>"Date et heure de la sortie :",
                'html5'=> true,
                'widget' => 'single_text'
            ])
            ->add('dateLimiteInscription', DateType::class, [
                'label' => "Date limite d'inscription :",
                'html5'=> true,
                'widget' => 'single_text'
            ])
            ->add('nbInscriptionMax', null, [
                'label' => 'Nombre de places :'
            ])

            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en min) :'
            ])

            ->add('infosSortie', null, [
                'label' => "Description :"
            ])

            ->add('ville', EntityType::class, [
                'class'=> Ville::class,
                'choice_label'=> 'nom',
                'mapped' => false,
                'label' => 'Ville :'
            ])

            ->add('lieu', EntityType::class, [
                'class'=> Lieu::class,
                'choice_label'=>'nom',
                'label' => 'Lieu :',
                'query_builder'=>function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('lv')->orderBy('lv.nom', 'ASC');
                }
            ])

            ->add('campus', EntityType::class, [
                'class'=>Campus::class,
                'choice_label'=>"nom",
                'label' => 'Campus organisateur:',
                'multiple' => false,
                'attr'=>['disabled'=>'disabled'
                ]
            ])

            ->add('btnCreee', SubmitType::class, [
                'label'=>'Créer'
            ])
            ->add('btnOuverte', SubmitType::class, [
                'label'=>'Publier'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
