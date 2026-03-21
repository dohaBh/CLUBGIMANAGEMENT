<?php

// src/Enum/StatutDmd.php - Garde tel quel, c'est parfait
namespace App\Enum;

enum StatutDmd: string {
    case EN_ATTENTE = 'en_attente';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    
    public function getLabel(): string {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::ACCEPTED => 'Acceptée',
            self::REJECTED => 'Rejetée',
        };
    }
}


// // Pour la DEMANDE D'ADHÉSION (le processus)
// enum StatutDmd: string {
//     case EN_ATTENTE = 'en_attente'; // En cours de traitement
//     case ACCEPTED = 'accepted';     // Demande acceptée
//     case REJECTED = 'rejected';     // Demande rejetée
// }