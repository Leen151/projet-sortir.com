<?php

namespace App\data;

use App\Entity\Campus;
use Symfony\Component\Validator\Constraints\Date;

class FiltresSorties
{
    /**
     * @var Campus
     */
    public $campus;

    /**
     * @var string
     */
    public $motClef = "";

    /**
     * @var Date
     */
    public $dateMin;

    /**
     * @var Date
     */
    public $dateMax;

    /**
     * @var boolean
     */
    public $participantOrganisateur = false;

    /**
     * @var boolean
     */
    public $participantInscrit = false;

    /**
     * @var boolean
     */
    public $participantNonInscrit = false;

    /**
     * @var boolean
     */
    public $sortiePassee = false;

}