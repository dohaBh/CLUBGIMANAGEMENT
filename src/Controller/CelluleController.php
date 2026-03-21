<?php

namespace App\Controller;

use App\Entity\Cellule;
use App\Entity\User;
use App\Form\CelluleType;
use App\Repository\CelluleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route('/cellule')]
class CelluleController extends AbstractController
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
     * Liste de toutes les cellules - visible par tous
     */
    #[Route('/', name: 'app_cellule_index', methods: ['GET'])]
    public function index(CelluleRepository $celluleRepository, UserRepository $userRepository): Response
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        // Authentification obligatoire
        if (!$userSession) {
            $this->addFlash('error', 'Accès réservé aux membres connectés.');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getManagedUser($userRepository);
        
        // Tout le monde peut voir la liste des cellules
        return $this->render('cellule/index.html.twig', [
            'cellules' => $celluleRepository->findAll(),
            'user' => $user,
        ]);
    }

/**
 * Créer une nouvelle cellule
 */
#[Route('/new', name: 'app_cellule_new', methods: ['GET', 'POST'])]
public function new(
    Request $request, 
    EntityManagerInterface $entityManager,
    UserRepository $userRepository,
    CelluleRepository $celluleRepository  // Ajoutez ce repository
): Response
{
    $user = $this->getManagedUser($userRepository);
    
    if (!$user) {
        $this->addFlash('error', 'Veuillez vous connecter pour créer une cellule.');
        return $this->redirectToRoute('app_login');
    }

    // Vérifier les permissions - Seul ROLE_RESPONSABLE peut créer des cellules
    if (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles())) {
        $this->addFlash('error', 'Accès réservé aux responsables.');
        return $this->redirectToRoute('equipe_responsable');
    }

    $cellule = new Cellule();
    $form = $this->createForm(CelluleType::class, $cellule);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // VÉRIFICATION 1: La cellule existe-t-elle déjà par son nom ?
        $nomCellule = $cellule->getNom();
        $existingCellule = $celluleRepository->findOneBy(['nom' => $nomCellule]);
        
        if ($existingCellule) {
            $this->addFlash('error', 
                'Une cellule avec le nom "' . $nomCellule . '" existe déjà. ' .
                'Veuillez choisir un autre nom.'
            );
            
            return $this->render('equipe/newCellule.html.twig', [
                'cellule' => $cellule,
                'form' => $form->createView(),
            ]);
        }
        
        // VÉRIFICATION 2: Le responsable est-il déjà responsable d'une autre cellule ?
        $responsable = $cellule->getResponsable();
        $existingCelluleByResponsable = $celluleRepository->findOneBy(['responsable' => $responsable]);
        
        if ($existingCelluleByResponsable) {
            $this->addFlash('error', 
                'L\'utilisateur "' . $responsable->getNom() . 
                '" est déjà responsable de la cellule "' . $existingCelluleByResponsable->getNom() . '". ' .
                'Veuillez choisir un autre responsable.'
            );
            
            return $this->render('equipe/newCellule.html.twig', [
                'cellule' => $cellule,
                'form' => $form->createView(),
            ]);
        }

        // Si toutes les vérifications passent, créer la cellule
        try {
            $entityManager->persist($cellule);
            $entityManager->flush();

            $this->addFlash('success', 'Cellule créée avec succès.');
            return $this->redirectToRoute('equipe_responsable', [], Response::HTTP_SEE_OTHER);
            
        } catch (\Exception $e) {
            // Capturer toute autre erreur d'intégrité
            $this->addFlash('error', 
                'Erreur lors de la création de la cellule. ' .
                'Vérifiez que le nom est unique et que le responsable n\'est pas déjà assigné.'
            );
            
            return $this->render('equipe/newCellule.html.twig', [
                'cellule' => $cellule,
                'form' => $form->createView(),
            ]);
        }
    }

    return $this->render('equipe/newCellule.html.twig', [
        'cellule' => $cellule,
        'form' => $form->createView(),
    ]);
}
    /**
     * Supprimer une cellule
     */
    #[Route('/{id}/delete', name: 'app_cellule_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Cellule $cellule, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getManagedUser($userRepository);
        
        if (!$user) {
            $this->addFlash('error', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier les permissions - Seul ROLE_RESPONSABLE peut supprimer des cellules
        if (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles())) {
            $this->addFlash('error', 'Accès réservé aux responsables.');
            return $this->redirectToRoute('equipe_responsable');
        }

        if ($this->isCsrfTokenValid('delete'.$cellule->getId(), $request->request->get('_token'))) {
            
            
            $entityManager->remove($cellule);
            $entityManager->flush();

            $this->addFlash('success', 'Cellule supprimée avec succès.');
        }

        return $this->redirectToRoute('equipe_responsable', [], Response::HTTP_SEE_OTHER);
    }
}