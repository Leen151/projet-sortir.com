<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ModifMotPasseType;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route ("/profil")
 */

class ParticipantController extends AbstractController
{
    /**
     * @Route("/{id}", name="participant_profil", requirements={"id": "\d+"})
     */
    public function profil(int $id, ParticipantRepository $participantRepository): Response
    {
        $participant = $participantRepository->find($id);
        if (!$participant) {
            throw $this->createNotFoundException("Utilisateur non trouvé");
        }

        return $this->render('participant/profil.html.twig', [
            "participant" => $participant
        ]);
    }


    /**
     * @Route("/modifier", name="participant_modifier")
     */
    public function modifier(Request $request,
                             EntityManagerInterface $entityManager,
                             SluggerInterface $slugger): Response
    {
        //$participant = $entityManager->getRepository(Participant::class)->find($id);
        $participant = $this->getUser();

        if (!$participant) {
            throw $this->createNotFoundException("Utilisateur non trouvé");
        }

        $profilForm = $this->createForm(ProfilType::class, $participant);
        $profilForm->handleRequest($request);

        if ($profilForm->isSubmitted() && $profilForm->isValid()) {
            $entityManager -> persist($participant);
            $entityManager->flush();
            //affichage message flash
            $this->addFlash('success', 'Profil modifié');
            //redirection
            return $this->redirectToRoute("participant_profil", ['id'=>$participant->getId()]);
        }

        return $this->render('participant/modification.html.twig', [
            "profilForm" => $profilForm->createView()
        ]);
    }


    /**
     * @Route("/modifiermotpasse", name="participant_modificationMotPasse")
     */
    public function modificationMotPasse(Request $request,
                                         UserPasswordHasherInterface $passwordHasher,
                                         EntityManagerInterface $entityManager): Response
    {
        /** @var Participant $participant */
        $participant = $this->getUser();

        $passwordForm = $this->createForm(ModifMotPasseType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            if(password_verify( $passwordForm->get('ancien_motPasse')->getData(),
                $participant->getPassword())) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $participant,
                    $passwordForm->get('nouveau_motPasse')->getData()
                );
                $participant->setPassword($hashedPassword);

                $entityManager -> persist($participant);
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe modifié !');

                return $this->redirectToRoute("participant_profil", ["id"=>$participant->getId()]);
            }
            else {
                $this->addFlash('error', 'Mot de passe actuel incorrect !');
            }
        }

        return $this->render('participant/modifMotPasse.html.twig',[
            "passwordForm" => $passwordForm->createView()
        ]);
    }


}
