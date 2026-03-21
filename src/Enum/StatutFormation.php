<?php

namespace App\Enum;

enum StatutFormation: string {
    case PLANIFIEE = 'planifee';
    case EN_COURS = 'en_cours';
    case ANNULEE = 'annulee';
	case TERMINEE = 'terminee';
}