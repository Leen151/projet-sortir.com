<?php

namespace App\Controller;

use App\data\FiltresSorties;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\AnnulationType;
use App\Form\FiltreType;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Services\MajEtatSortie;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



class SortieController extends AbstractController
{
    /**
     * @Route("/sorties", name="sorties_liste")
     */
    public function liste(SortieRepository $sortieRepository, Request $request, MajEtatSortie $majEtatSortie): Response
    {
//        $sorties = $sortieRepository->findAll();
//
//        return $this->render('sortie/liste.html.twig', [
//            'sorties' => $sorties,
//        ]);
        $majEtatSortie->changementEtatSelonDate();

        $data = new FiltresSorties();
        /** @var  Participant $user */
        $user = $this->getUser();

        $formFiltre = $this->createForm(FiltreType::class, $data);
        $formFiltre->handleRequest($request);
        //récupération des données grâce à la requête personnalisée avec les filtres
        $sorties = $sortieRepository->listeAvecFiltre($data, $user);


        //$sorties = $sortieRepository->findAll();
        return $this->render('sortie/liste.html.twig', [
            'sorties'=>$sorties,
            'formFiltre'=>$formFiltre->createView()
        ]);
    }

    /**
     * @Route("/details/{id}", name="sortie_details", requirements={"id"="\d+"})
     */
    public function details(int $id, SortieRepository $sortieRepository): Response
    {
        $sortie = $sortieRepository->find($id);

        if(!$sortie) {
            throw $this->createNotFoundException("Sortie non trouvée");
        }

        return $this->render('sortie/details.html.twig', [
            "sortie"=>$sortie
        ]);
    }


    /**
     * @Route("/creer", name="sortie_creer")
     */
    public function creer(EtatRepository $etatRepository,
                          EntityManagerInterface $entityManager,
                          Request $request): Response
    {

        /** @var  Participant $user */
        $user = $this->getUser();

        $sortie=new Sortie();
        $sortieForm=$this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);


        if ($sortieForm->get('btnCreee')->isClicked()) {
            $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Créée']));
        }
        elseif ($sortieForm->get('btnOuverte')->isClicked()) {
            $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Ouverte']));
        }

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $sortie->setOrganisateur($user);
            $sortie->setCampus($user->getCampus());
            $entityManager -> persist($sortie);
            $entityManager->flush();

            //creation message flash
            $this->addFlash('success', 'Sortie ajoutée!');
            return $this->redirectToRoute('sortie_details', ['id'=>$sortie->getId()]);
        }

        return $this->renderForm('sortie/creer.html.twig', [
            "sortieForm"=>$sortieForm,
            "sortie"=>$sortie
        ]);
    }


    /**
     * @Route("/modifier/{id}", name="sortie_modifier", requirements={"id"="\d+"})
     */
    public function modifierSortie(int $id,
                                   EntityManagerInterface $entityManager,
                                   Request $request,
                                   EtatRepository $etatRepository): Response
    {
        /** @var  Participant $user */
        $user = $this->getUser();
        $sortie=$entityManager->getRepository(Sortie::class)->find($id);
        if (!$sortie) {
            throw $this->createNotFoundException("Cette sortie n'existe pas");
        }

        if (($sortie->getOrganisateur() === $this->getUser()) and (($sortie->getEtat()->getLibelle()) == 'Créée')){
            $sortieForm=$this->createForm(SortieType::class, $sortie);
            $sortieForm->handleRequest($request);

            if ($sortieForm->get('btnCreee')->isClicked()) {
                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Créée']));
            }
            elseif ($sortieForm->get('btnOuverte')->isClicked()) {
                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Ouverte']));
            }

            if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
                $sortie->setOrganisateur($user);
                $sortie->setCampus($user->getCampus());
                $entityManager -> persist($sortie);
                $entityManager->flush();

                $this->addFlash('success', 'Sortie modifiée');
                return $this->redirectToRoute('sortie_details', ['id'=>$sortie->getId()]);
            }
        }
        else {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette sortie');
            return $this->redirectToRoute('sorties_liste');
        }
        return $this->renderForm('sortie/modifier.html.twig', [
            "sortieForm"=>$sortieForm
        ]);
    }

    /**
     *@Route ("/supprimer/{id}", name="sortie_supprimer")
     */
    public function supprimer(int $id, EntityManagerInterface $entityManager): Response {

        $sortie=$entityManager->getRepository(Sortie::class)->find($id);
        if (($sortie->getOrganisateur() === $this->getUser()) and (($sortie->getEtat()->getLibelle()) == 'Créée')){
            $entityManager->remove($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Sortie supprimée!');
        }
        else{
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette sortie');
            return $this->redirectToRoute('sorties_liste');
        }
        return $this->redirectToRoute('sorties_liste');
    }

    /////////////////////////////////////////////////////////////////////////////
    //inscription et désinscription sorties (avec les méthodes add et remove fournies (cf sortie controller)
    /**
     * @Route("/inscription/{id}", name="sortie_inscriptionparticipant", requirements={"id"="\d+"})
     */
    public function inscriptionParticipant(EtatRepository $etatRepository, EntityManagerInterface $entityManager, Sortie $sortie) {
        /** @var  Participant $participant */
        $participant = $this->getUser();

        if (($sortie->getEtat()->getLibelle()) == 'Ouverte') {
            $sortie->addParticipant($participant);

            if (count($sortie->getParticipants()) == $sortie->getNbInscriptionMax())  {
                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Clôturée']));
            }
            $entityManager->persist($sortie);
            $entityManager->flush();
        }

        else{
            $this->addFlash('error','Sortie fermée');
        }

        return $this->redirectToRoute('sorties_liste');
    }


    /**
     * @Route("/desinscription/{id}", name="sortie_desinscriptionparticipant", requirements={"id"="\d+"})
     */
    public function desinscriptionParticipant(EtatRepository $etatRepository,
                                              EntityManagerInterface $entityManager,
                                              Sortie $sortie)
    {
        /** @var  Participant $participant */
        $today = new DateTime('now');
        //getTimeStamp() transforme la date en nombre de seconde depuis une date fixe (on appel ça des dates en format Unix)
        //permet de faire des comparaisons de dates
        $todayUnix = $today->getTimestamp();

        $dateLimUnix = $sortie->getDateLimiteInscription()->getTimestamp();

        $participant = $this->getUser();
        $sortie->getParticipants()->contains($participant);

        if ($sortie->getParticipants()->contains($participant)){
            if (($sortie->getEtat()->getLibelle() == 'Ouverte') or ($sortie->getEtat()->getLibelle() == 'Clôturée')) {
                $sortie->removeParticipant($participant);

                //on vérifie que la sortie qui était clôturé car complète peut être réouverte par rapport à la date limite d'inscription
                if ((count($sortie->getParticipants()) <= $sortie->getNbInscriptionMax()) && ($dateLimUnix > $todayUnix)){
                    $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Ouverte']));
                }
                $entityManager->persist($sortie);
                $entityManager->flush();

                $this->addFlash('message', 'Vous êtes désinscrit');

            }elseif ($sortie->getEtat()->getLibelle() == 'En cours'){
                $this->addFlash('error',"Activité commencée, impossible de se désinscrire");

            }elseif ($sortie->getEtat()->getLibelle() == 'Passée'){
                $this->addFlash('error',"Activité déjà terminée");
            }

        }else{
            $this->addFlash('error',"Vous n'êtes pas inscrit à la sortie");
        }

        $entityManager->persist($sortie);
        $entityManager->flush();

        return $this->redirectToRoute('sorties_liste');
    }


    /**
     * @Route("/annulation/{id}", name="sortie_annulation", requirements={"id"="\d+"})
     */
    public function annulationSortie(EtatRepository $etatRepository,
                                     EntityManagerInterface $entityManager,
                                     Sortie $sortie,
                                     Request $request)
    {
        /** @var  Sortie $organisateur */
        /** @var  Participant $participant */
        $participant = $this->getUser();

        $formAnnulation = $this->createForm(AnnulationType::class, $sortie);
        $formAnnulation->handleRequest($request);

        if (($participant = $sortie->getOrganisateur()) or ($participant->getRoles()== "ROLE_ADMIN" )){
            if ($formAnnulation->isSubmitted() && $formAnnulation->isValid()) {
                $sortie->setMotifAnnulation($formAnnulation['motifAnnulation']->getData());
                $sortie->setEtat($etatRepository->findOneBy(['libelle' => 'Annulée']));

                $entityManager->persist($sortie);
                $entityManager->flush();
                $this->addFlash('success', 'La sortie a été annulée !');
                return $this->redirectToRoute('sorties_liste');
            }
        }
        return $this->render('sortie/Annulation.html.twig', [
            'page_name' => 'Annuler sortie',
            'sortie'=> $sortie,
            'formAnnulation' => $formAnnulation->createView()
        ]);
    }



}
