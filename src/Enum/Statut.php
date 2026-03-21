<?php

// src/Enum/Statut.php
namespace App\Enum;

enum Statut: string {
    case EN_ATTENTE = 'en_attente';      // En attente d'approbation
    case ACTIF = 'actif';                // Accepté et actif
    case INACTIF = 'inactif';            // Désactivé
    case REJETE = 'rejete';              // Rejeté par un pilote
    
    public function getLabel(): string {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::ACTIF => 'Actif',
            self::INACTIF => 'Inactif',
            self::REJETE => 'Rejeté',
        };
    }
}


// // Pour l'UTILISATEUR lui-même
// enum Statut: string {
//     case ACTIF = 'actif';          // Peut se connecter
//     case EN_ATTENTE = 'en_attente'; // Inscrit mais pas approuvé
//     case REFUSE = 'refuse';        // Refusé par le pilotage
// }