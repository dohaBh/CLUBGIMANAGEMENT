<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use App\Repository\UserRepository;
use App\Enum\StatutFormation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route('/formations')]
class FormationController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Helper method pour obtenir l'utilisateur managé par Doctrine
     */
    private function getManagedUser(UserRepository $userRepository)
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            return null;
        }

        // Récupérer l'User depuis la base de données (version managée par Doctrine)
        return $userRepository->find($userSession->getId());
    }

    /**
     * Liste de toutes les formations - visible par tous
     */
    #[Route('/', name: 'app_formations_index', methods: ['GET'])]
    public function index(FormationRepository $formationRepository, UserRepository $userRepository): Response
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        // Authentification obligatoire
        if (!$userSession) {
            $this->addFlash('error', 'Accès réservé aux membres connectés.');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getManagedUser($userRepository);
        
        // Récupération des formations selon le rôle
        if ($user->isPilotage() || in_array('ROLE_RESPONSABLE', $user->getRoles())) {
            $formations = $formationRepository->findAll();
            $pendingCount = $formationRepository->count(['isApproved' => false]);
//             Elle compte le nombre d’enregistrements dans la table formation
//              👉 dont le champ isApproved vaut false
                // En SQL, ça équivaut à :
                // SELECT COUNT(*) 
                // FROM formation 
                // WHERE is_approved = 0;

        } else {
            $formations = $formationRepository->createQueryBuilder('f')
                ->where('f.isApproved = true OR f.createdBy = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
            $pendingCount = 0;
        }
        // Afficher :
        // toutes les formations approuvées
        // + les formations créées par l’utilisateur connecté (même si elles ne sont pas encore approuvées)

        return $this->render('formation/index.html.twig', [
            'formations' => $formations,
            'pendingCount' => $pendingCount,
        ]);
    }

    /**
     * Créer une nouvelle formation
     */
    #[Route('/new', name: 'app_formations_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getManagedUser($userRepository);
        
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour créer une formation.');
            return $this->redirectToRoute('app_login');
        }

        $formation = new Formation();
        $formation->setCreatedBy($user);
        $formation->setCreatedAt(new \DateTimeImmutable());
        
        // Approuver automatiquement si l'utilisateur est responsable
        if ($user->isPilotage() || in_array('ROLE_RESPONSABLE', $user->getRoles())) {
            $formation->setIsApproved(true);
            $flashMessage = 'Formation créée avec succès.';
            $flashType = 'success';
        } else {
            $formation->setIsApproved(false);
            $flashMessage = 'Formation créée. Elle sera visible après approbation par un responsable.';
            $flashType = 'warning';
        }

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation: maximum 6 formateurs
            if ($formation->getFormateurs()->count() > 6) {
                $this->addFlash('error', 'Maximum 6 formateurs autorisés.');
                return $this->render('formation/new.html.twig', [
                    'formation' => $formation,
                    'form' => $form->createView(),
                ]);
            }

            // S'assurer que tous les formateurs sont managés par Doctrine
            $formateurIds = [];
            foreach ($formation->getFormateurs() as $formateur) {
                $formateurIds[] = $formateur->getId();
            }
            
            // Vider la collection et recharger avec les entités managées
            $formation->getFormateurs()->clear();
            
            foreach ($formateurIds as $formateurId) {
                $managedFormateur = $userRepository->find($formateurId);
                if ($managedFormateur) {
                    $formation->addFormateur($managedFormateur);
                }
            }

            $entityManager->persist($formation);
            $entityManager->flush();

            $this->addFlash($flashType, $flashMessage);
            return $this->redirectToRoute('app_formations_index');
        }

        return $this->render('formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Formations en attente d'approbation (responsables seulement)
     * Priority: 10 pour être évaluée AVANT /{id}
     */
    #[Route('/pending', name: 'app_formations_pending', methods: ['GET'], priority: 10)]
    public function pending(
        FormationRepository $formationRepository,
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getManagedUser($userRepository);
        
        if (!$user || (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles()))) {
            $this->addFlash('error', 'Accès réservé aux responsables.');
            return $this->redirectToRoute('app_formations_index');
        }

        $pendingFormations = $formationRepository->findBy(['isApproved' => false]);

        return $this->render('formation/pending.html.twig', [
            'formations' => $pendingFormations,
        ]);
    }

    /**
     * Voir les détails d'une formation
     * ATTENTION: Cette route doit être APRÈS /new et /pending
     */
    #[Route('/{id}', name: 'app_formations_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        string $id,
        FormationRepository $formationRepository,
        UserRepository $userRepository
    ): Response
    {
        // Convertir l'ID en entier et valider
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        $user = $userSession ? $this->getManagedUser($userRepository) : null;
        
        // Vérifier si l'utilisateur peut voir cette formation
        if (!$formation->isApproved()) {
            if (!$user) {
                $this->addFlash('error', 'Cette formation n\'est pas encore approuvée.');
                return $this->redirectToRoute('app_formations_index');
            }
            
            $isCreator = $formation->getCreatedBy()->getId() === $user->getId();
            $isResponsable = $user->isPilotage() || in_array('ROLE_RESPONSABLE', $user->getRoles());
            
            if (!$isCreator && !$isResponsable) {
                $this->addFlash('error', 'Cette formation n\'est pas encore approuvée.');
                return $this->redirectToRoute('app_formations_index');
            }
        }

        return $this->render('formation/show.html.twig', [
            'formation' => $formation,
        ]);
    }

    /**
     * Éditer une formation 
     */
    #[Route('/{id}/edit', name: 'app_formations_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        string $id,
        Request $request,
        FormationRepository $formationRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        $user = $this->getManagedUser($userRepository);
        
        // Vérifier les permissions 
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        $isCreator = $formation->getCreatedBy()->getId() === $user->getId();
        $isResponsable = $user->isPilotage() || in_array('ROLE_RESPONSABLE', $user->getRoles());

        if (!$isCreator && !$isResponsable) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de modifier cette formation.');
            return $this->redirectToRoute('app_formations_index');
        }

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation: maximum 6 formateurs
            if ($formation->getFormateurs()->count() > 6) {
                $this->addFlash('error', 'Maximum 6 formateurs autorisés.');
                return $this->render('formation/edit.html.twig', [
                    'formation' => $formation,
                    'form' => $form->createView(),
                ]);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Formation modifiée avec succès.');
            return $this->redirectToRoute('app_formations_index');
        }

        return $this->render('formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprimer une formation 
     */
    #[Route('/{id}/delete', name: 'app_formations_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        string $id,
        Request $request,
        FormationRepository $formationRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        $user = $this->getManagedUser($userRepository);
        
        // Vérifier les permissions 
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        $isCreator = $formation->getCreatedBy()->getId() === $user->getId();
        $isResponsable = $user->isPilotage() || in_array('ROLE_RESPONSABLE', $user->getRoles());

        if (!$isCreator && !$isResponsable) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de supprimer cette formation.');
            return $this->redirectToRoute('app_formations_index');
        }

        if ($this->isCsrfTokenValid('delete'.$formation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();

            $this->addFlash('success', 'Formation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_formations_index');
    }

    /**
     * Approuver une formation (responsables seulement)
     */
    #[Route('/{id}/approve', name: 'app_formations_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(
        string $id,
        Request $request,
        FormationRepository $formationRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        $user = $this->getManagedUser($userRepository);
        
        if (!$user || (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles()))) {
            $this->addFlash('error', 'Accès réservé aux responsables.');
            return $this->redirectToRoute('app_formations_index');
        }

        if ($this->isCsrfTokenValid('approve'.$formation->getId(), $request->request->get('_token'))) {
            $formation->setIsApproved(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Formation approuvée avec succès.');
        }

        return $this->redirectToRoute('app_formations_pending');
    }

    /**
     * Rejeter une formation (responsables seulement)
     */
    #[Route('/{id}/reject', name: 'app_formations_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(
        string $id,
        Request $request,
        FormationRepository $formationRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        $user = $this->getManagedUser($userRepository);
        
        if (!$user || (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles()))) {
            $this->addFlash('error', 'Accès réservé aux responsables.');
            return $this->redirectToRoute('app_formations_index');
        }

        if ($this->isCsrfTokenValid('reject'.$formation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Formation rejetée et supprimée.');
        }

        return $this->redirectToRoute('app_formations_pending');
    }

    /**
     * S'inscrire à une formation
     */
    #[Route('/{id}/inscrire', name: 'app_formations_inscrire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function inscrire(
        string $id,
        Request $request,
        FormationRepository $formationRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        $user = $this->getManagedUser($userRepository);
        
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter pour vous inscrire.');
            return $this->redirectToRoute('app_login');
        }

        if (!$formation->isApproved()) {
            $this->addFlash('error', 'Cette formation n\'est pas encore approuvée.');
            return $this->redirectToRoute('app_formations_index');
        }

        if ($formation->getStatutFormation() !== StatutFormation::PLANIFIEE) {
            $this->addFlash('error', 'Cette formation n\'est plus disponible pour inscription.');
            return $this->redirectToRoute('app_formations_index');
        }

        // Vérifier si déjà inscrit
        if ($formation->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette formation.');
            return $this->redirectToRoute('app_formations_index');
        }

        // Vérifier la limite de participants
        if ($formation->getNbParticipants() > 0 && 
            $formation->getParticipants()->count() >= $formation->getNbParticipants()) {
            $this->addFlash('error', 'La formation a atteint le nombre maximum de participants.');
            return $this->redirectToRoute('app_formations_index');
        }

        $formation->addParticipant($user);
        $entityManager->flush();

        $this->addFlash('success', 'Inscription réussie !');
        return $this->redirectToRoute('app_formations_index');
    }

    /**
     * Se désinscrire d'une formation
     */
    #[Route('/{id}/desinscrire', name: 'app_formations_desinscrire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function desinscrire(
        string $id,
        Request $request,
        FormationRepository $formationRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        $user = $this->getManagedUser($userRepository);
        
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier si inscrit
        if (!$formation->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous n\'êtes pas inscrit à cette formation.');
            return $this->redirectToRoute('app_formations_index');
        }

        $formation->removeParticipant($user);
        $entityManager->flush();

        $this->addFlash('success', 'Désinscription réussie.');
        return $this->redirectToRoute('app_formations_index');
    }

    /**
     * Voir la liste des participants d'une formation
     */
    #[Route('/{id}/participants', name: 'app_formations_participants', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function participants(
        string $id,
        FormationRepository $formationRepository
    ): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', 'ID de formation invalide.');
            return $this->redirectToRoute('app_formations_index');
        }
        
        $formation = $formationRepository->find((int)$id);
        
        if (!$formation) {
            $this->addFlash('error', 'Formation introuvable.');
            return $this->redirectToRoute('app_formations_index');
        }

        return $this->render('formation/participants.html.twig', [
            'formation' => $formation,
            'participants' => $formation->getParticipants(),
        ]);
    }
}