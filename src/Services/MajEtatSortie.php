<?php

namespace App\Services;

use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class MajEtatSortie
{
    private $etatRepository;
    private $sortieRepository;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager,EtatRepository $etatRepository, SortieRepository $sortieRepository)
    {
        $this->sortieRepository = $sortieRepository;
        $this->etatRepository = $etatRepository;
        $this->entityManager = $entityManager;
    }


    public function changementEtatSelonDate()
    {
        //création de variables pour code plus lisible
        $toCloturee = $this->etatRepository->findOneBy(['libelle'=>'Clôturée']);
        $toEnCours = $this->etatRepository->findOneBy(['libelle'=>'En cours']);
        $toPassee = $this->etatRepository->findOneBy(['libelle'=>'Passée']);
        $toArchivee = $this->etatRepository->findOneBy(['libelle'=>'Archivée']);

        //récupérer les sorties "ouverte" à passer en clôturée (requête)
        //maj de l'état --> clôturée
        $sorties = $this->sortieRepository->findToCloture();
        foreach ($sorties as $sortie){
            $sortie->setEtat($toCloturee);
            $this->entityManager->persist($sortie);
        }
        $this->entityManager->flush();


        //récupérer les "clôturée" à passer à en cours + maj de l'état --> en cours
        $sorties = $this->sortieRepository->findToEnCours();
        foreach ($sorties as $sortie){
            $sortie->setEtat($toEnCours);
            $this->entityManager->persist($sortie);
        }
        $this->entityManager->flush();


        //les "en cours" (dateHeureDebut + durée) --> passage à "passée"
        $sorties = $this->sortieRepository->findToPassee();
        foreach ($sorties as $sortie){
            $today = time(); //date en unix (temps en secondes depuis une date donnée)
            $dateDebutUnix = $sortie->getDateHeureDebut()->getTimestamp(); //transforme la datetime de début en Unix
            $duree = $sortie->getDuree();
            $dureeSecondes = $duree*60;

            //on vérifie que la sortie est finie (comparaison de la date de début + durée avec la date et heure actuelle)
            if ($today > ($dateDebutUnix+$dureeSecondes)){
                $sortie->setEtat($toPassee);
                $this->entityManager->persist($sortie);
            }
        }
        $this->entityManager->flush();


        //passée de plus de 30jours --> archivée
        $sorties = $this->sortieRepository->findToArchivage();
        foreach ($sorties as $sortie){
            $sortie->setEtat($toArchivee);
            $this->entityManager->persist($sortie);
        }
        $this->entityManager->flush();

    }
}