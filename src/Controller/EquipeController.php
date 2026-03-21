<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\DemandeAdhesion;
use App\Enum\Statut;
use App\Enum\StatutDmd;
use Doctrine\ORM\EntityManagerInterface; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EquipeController extends AbstractController
{
    #[Route('/equipe', name: 'app_equipe')]
    public function index(Request $request): Response
    {
        // Vérifier si l'utilisateur est connecté via VOTRE système (session)
        $session = $request->getSession();
        
        // Vérifier les clés que vous stockez dans LoginController
        if (!$session->has('user_id') || !$session->has('user')) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer l'utilisateur depuis la session
        $user = $session->get('user');
        
        // Vérifier si c'est un responsable
        $isResponsable = $user->isPilotage() || in_array('ROLE_RESPONSABLE', $user->getRoles());
        
        if ($isResponsable) {
            return $this->redirectToRoute('equipe_responsable');
        } else {
            return $this->redirectToRoute('equipe_membre');
        }
    }
    
    #[Route('/equipe/responsable', name: 'equipe_responsable')]
    public function responsableView(Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier l'authentification
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        $user = $session->get('user');
        
        // Vérifier si c'est un responsable
        if (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles())) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
            return $this->redirectToRoute('equipe_membre');
        }
        
        // 1. Récupérer toutes les demandes en attente
        $pendingDemandes = $em->getRepository(DemandeAdhesion::class)->findBy([
            'statutDmd' => StatutDmd::EN_ATTENTE
        ], ['dateDemande' => 'ASC']);
        
        // 2. Récupérer tous les utilisateurs ACTIFS du club
        //createQueryBuilder('u') = Crée un constructeur de requête avec l'alias 'u' pour User
        $allUsers = $em->getRepository(User::class)->createQueryBuilder('u') //"Je vais chercher des objets User, et je les appelle u pour la requête"
            ->where('u.statut = :status')  // Condition de recherche
            ->setParameter('status', Statut::ACTIF) //On CHERCHE où statut = 'actif'
            ->orderBy('u.nom', 'ASC')//tri de A à Z 
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
        
        // 3. Statistiques
        $stats = [
            'total_membres' => count($allUsers),
            'pending_requests' => count($pendingDemandes),
            'responsables' => count(array_filter($allUsers, fn($u) => $u->isPilotage())),
            'membres' => count(array_filter($allUsers, fn($u) => !$u->isPilotage())),
        ];
        
        return $this->render('equipe/responsable.html.twig', [
            'pendingDemandes' => $pendingDemandes,
            'allUsers' => $allUsers,
            'stats' => $stats,
        ]);
    }
    
    #[Route('/equipe/membre', name: 'equipe_membre')]
    public function membreView(Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier l'authentification
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        // Récupérer tous les utilisateurs ACTIFS du club
        $allUsers = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.statut = :status')
            ->setParameter('status', Statut::ACTIF)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('equipe/membre.html.twig', [
            'allUsers' => $allUsers,
        ]);
    }
    
    #[Route('/equipe/demande/{id}/accepter', name: 'app_demande_accepter')]
    public function accepterDemande(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        // Vérifier l'authentification et les permissions
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        $currentUser = $session->get('user');
        
        if (!$currentUser->isPilotage() && !in_array('ROLE_RESPONSABLE', $currentUser->getRoles())) {
            $this->addFlash('error', 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
            return $this->redirectToRoute('equipe_membre');
        }
        
        // Récupérer la demande
        $demande = $em->getRepository(DemandeAdhesion::class)->find($id);
        
        if (!$demande) {
            $this->addFlash('error', 'Demande non trouvée.');
            return $this->redirectToRoute('equipe_responsable');
        }
        
        $user = $demande->getUser();
        
        // Vérifier que l'utilisateur a bien vérifié son email
        if (!$user->isVerified()) {
            $this->addFlash('error', 'Cet utilisateur n\'a pas encore vérifié son email.');
            return $this->redirectToRoute('equipe_responsable');
        }
        
        // Mettre à jour les statuts
        $user->setStatut(Statut::ACTIF);
        $demande->setStatutDmd(StatutDmd::ACCEPTED);
        
        // Assigner la cellule si spécifiée
        if ($demande->getCellule()) {
            $user->setCellule($demande->getCellule());
        }
        
        $em->flush();
        
        $this->addFlash('success', 
            sprintf('Demande de %s %s acceptée avec succès !', 
                $user->getPrenom(), 
                $user->getNom()
            )
        );
        
        return $this->redirectToRoute('equipe_responsable');
    }
    #[Route('/equipe/demande/{id}/refuser', name: 'app_demande_refuser', methods: ['GET']) ]
    public function refuserDemande(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        // Vérifier l'authentification et les permissions
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        $currentUser = $session->get('user');
        
        if (!$currentUser->isPilotage() && !in_array('ROLE_RESPONSABLE', $currentUser->getRoles())) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('equipe_membre');
        }
        
        $demande = $em->getRepository(DemandeAdhesion::class)->find($id);
        
        if (!$demande) {
            $this->addFlash('error', 'Demande non trouvée.');
            return $this->redirectToRoute('equipe_responsable');
        }
        
        $user = $demande->getUser();
        
        // Mettre à jour les statuts
        $user->setStatut(Statut::REJETE);
        $demande->setStatutDmd(StatutDmd::REJECTED);
        
        $em->flush();
        
        $this->addFlash('warning', 
            sprintf('Demande de %s %s refusée.', 
                $user->getPrenom(), 
                $user->getNom()
            )
        );
        
        return $this->redirectToRoute('equipe_responsable');
    }

    #[Route('/equipe/membre/{id}/supprimer', name: 'app_membre_supprimer', methods: ['POST'])]
    public function supprimerMembre(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        // Vérifier l'authentification et les permissions
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        $currentUser = $session->get('user');
        
        if (!$currentUser->isPilotage() && !in_array('ROLE_RESPONSABLE', $currentUser->getRoles())) {
            $this->addFlash('error', 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
            return $this->redirectToRoute('equipe_membre');
        }
        
        // Empêcher un responsable de se supprimer lui-même
        if ($currentUser->getId() === $id) {
            $this->addFlash('error', 'Vous ne pouvez pas vous supprimer vous-même.');
            return $this->redirectToRoute('equipe_responsable');
        }
        
        // Récupérer le membre à supprimer
        $membre = $em->getRepository(User::class)->find($id);
        
        if (!$membre) {
            $this->addFlash('error', 'Membre non trouvé.');
            return $this->redirectToRoute('equipe_responsable');
        }
        
        // Empêcher de supprimer un autre responsable
        if ($membre->isPilotage() || in_array('ROLE_RESPONSABLE', $membre->getRoles())) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer un autre responsable.');
            return $this->redirectToRoute('equipe_responsable');
        }
        
        // Récupérer la raison et les détails
        $raison = $request->request->get('raison_suppression', 'Raison non spécifiée');
        $details = $request->request->get('details_suppression', '');
        
        // Récupérer l'historique existant ou créer un nouveau tableau
        $historique = $session->get('historique_suppressions', []);
        
        // Ajouter la nouvelle suppression à l'historique
        $historique[] = [
            'membre_id' => $membre->getId(),
            'membre_nom' => $membre->getNom(),
            'membre_prenom' => $membre->getPrenom(),
            'membre_email' => $membre->getEmail(),
            'raison' => $raison,
            'details' => $details,
            'date' => date('d/m/Y H:i'),
            'responsable_nom' => $currentUser->getNom(),
            'responsable_prenom' => $currentUser->getPrenom(),
            'responsable_id' => $currentUser->getId()
        ];
        
        // Garder seulement les 50 dernières suppressions
        if (count($historique) > 50) {
            $historique = array_slice($historique, -50);
        }
        
        // Sauvegarder dans la session
        $session->set('historique_suppressions', $historique);
        
        //Supprimer définitivement
        $em->remove($membre);
        
        $em->flush();
        
        $this->addFlash('warning', 
            sprintf('Membre %s %s a été suspendu. Raison: %s', 
                $membre->getPrenom(), 
                $membre->getNom(),
                $raison
            )
        );
        
        return $this->redirectToRoute('equipe_responsable');
    }
    #[Route('/equipe/clear-historique', name: 'app_clear_historique', methods: ['POST'])]
    public function clearHistorique(Request $request): Response
    {
        // Vérifier l'authentification et les permissions
        $session = $request->getSession();
        if (!$session->has('user_id')) {
            return $this->json(['success' => false, 'message' => 'Non authentifié']);
        }
        
        $currentUser = $session->get('user');
        if (!$currentUser->isPilotage() && !in_array('ROLE_RESPONSABLE', $currentUser->getRoles())) {
            return $this->json(['success' => false, 'message' => 'Non autorisé']);
        }
        
        // Vider l'historique
        $session->remove('historique_suppressions');
        
        return $this->json(['success' => true]);
    }
}