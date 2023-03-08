<?php

namespace App\Repository;

use App\data\FiltresSorties;
use App\Entity\Participant;
use App\Entity\Sortie;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 *
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function add(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function listeAvecFiltre(FiltresSorties $filtre, Participant $user)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.etat', 'e')->addSelect('e');
        $queryBuilder->leftJoin('s.participants', 'p')->addSelect('p');
        $queryBuilder->join('s.campus', 'c')->addSelect('c');
        $queryBuilder->join('s.organisateur', 'o')->addSelect('o');
        $queryBuilder->join('s.lieu', 'l')->addSelect('l');

        //les sorties "archivée" ne seront pas affichéés
        $queryBuilder ->andWhere('e.libelle != :archivee')
                      ->setParameter('archivee', 'Archivée');


        //filtre les sorties "créée" --> que celle de l'utilisateur en cours
        $queryBuilder ->andWhere('e.libelle != :creee')
            ->orWhere('s.organisateur = :user AND e.libelle = :creee')
            ->setParameter('creee', 'Créée')
            ->setParameter('user', $user);

        //trie les sorties par date
        $queryBuilder->addOrderBy('s.dateHeureDebut', 'ASC');


        //filtre par mot clef
        if (!empty($filtre->motClef)){
            $queryBuilder ->andWhere('s.nom LIKE :motClef')
                ->setParameter('motClef', "%{$filtre->motClef}%");
        }

        //filtre selon le campus
        if (!empty($filtre->campus)){
            $queryBuilder ->andWhere('c.id IN (:campus)')
                ->setParameter('campus', $filtre->campus);
        }

        //filtre en fonction des dates min et max
        if(!empty($filtre->dateMin)){
            $queryBuilder ->andWhere('s.dateHeureDebut >= :dateMin')
                ->setParameter('dateMin', $filtre->dateMin);
        }
        if(!empty($filtre->dateMax)){
            $queryBuilder ->andWhere('s.dateHeureDebut <= :dateMax')
                ->setParameter('dateMax', $filtre->dateMax);
        }

        if(!empty($filtre->participantOrganisateur)){
            $queryBuilder ->andWhere('s.organisateur = :user')
                ->setParameter('user', $user);
        }


//Le fait de mettre que l'autre doit être vide permet d'avoir un résultat avec les 2 de cocher
        if(!empty($filtre->participantInscrit) AND empty($filtre->participantNonInscrit)){
            $queryBuilder ->andWhere(':user MEMBER OF s.participants')
                ->setParameter('user', $user);
        }
        if (!empty($filtre->participantNonInscrit) AND empty($filtre->participantInscrit)){
            $queryBuilder ->andWhere(':user NOT MEMBER OF s.participants')
                ->setParameter('user', $user);
        }


        if(!empty($filtre->sortiePassee)){
            $queryBuilder ->andWhere('e.libelle = :sortiePassee')
                ->setParameter('sortiePassee', 'Passée');
        }

        $query = $queryBuilder->getQuery();

        $paginator = new Paginator($query);
        return $paginator;
        //return $query->getResult();
        //return $this->findAll();
    }


    ///////////////////////////////////////////////////////////////////////
    //cherche les sorties "ouvertes" qui peuvent etre cloturées (date limite inscription dépassée)
    public function findToCloture(){
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.etat', 'e')->addSelect('e');

        $queryBuilder ->andWhere('e.libelle = :ouverte')
            ->setParameter('ouverte','Ouverte')
            ->andWhere(':today > s.dateLimiteInscription')
            ->setParameter('today', new DateTime('now'));
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }


    //cherche les sorties "cloturée" à passer à "en cours"
    public function findToEnCours(){
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.etat', 'e')->addSelect('e');

        $queryBuilder ->andWhere('e.libelle = :cloturee')
            ->setParameter('cloturee','Clôturée')
            ->andWhere(':today >= s.dateHeureDebut')
            ->setParameter('today', new DateTime('now'));
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }


    // chercher les sorties "en cours"
    // le filtre pour celles qui sont terminées sera fait dans le service
    // (pb de récupérer la valeur de durée et de l'ajouter)
    public function findToPassee(){
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.etat', 'e')->addSelect('e');

        $queryBuilder ->andWhere('e.libelle = :encours')
            ->setParameter('encours','En cours')
            ->andWhere(':today >= s.dateHeureDebut')
            ->setParameter('today', new DateTime('now'));
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }


    //cherche les sorties "passée" ou "annulée" qui doivent être archivées (ont eu lieu il y a plus de 30j)
    public function findToArchivage(){
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.etat', 'e')->addSelect('e');

        $queryBuilder ->andWhere('e.libelle = :passee')
            ->orWhere('e.libelle = :annulee')
            ->setParameter('passee','Passée')
            ->setParameter('annulee','Annulée')
            ->andWhere('s.dateHeureDebut < :datearchivage')
            ->setParameter('datearchivage', new DateTime('now - 1month'));
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }
}
