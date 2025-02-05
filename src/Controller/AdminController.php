<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Form\UserType;




#[IsGranted('ROLE_USER', statusCode: 403, message: 'You must be logged in.')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response{
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/test1', name: 'app_admin_test1')]
    public function index_test(): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // or add an optional message - seen by developers
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to access a
        page without having ROLE_ADMIN');

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/test2', name: 'app_admin_test2')]
    #[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, message: 'You are not allowed to access the Super admin dashboard.')]
    public function index_test2(): Response {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    //affichage de tous les utilisateurs
    #[Route('/admin/list', name: 'list_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function listUser(EntityManagerInterface $entityManager): Response{
        $repository = $entityManager->getRepository(User::class);
        $users = $repository->findBy([], ['id'=>'ASC']);
    
        return $this->render('admin/list.html.twig', [
            'users' => $users,
        ]);
    }
    
    //Supprime un utilisateur
    #[Route('/admin/user/delete/{id}', name: 'delete_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): Response{
        
        $repository = $entityManager->getRepository(User::class);
        $user = $repository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        //Protection pour éviter de supprimer un super admin
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'Impossible de supprimer un Super Admin.');
            return $this->redirectToRoute('list_user');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('list_user');
    }

    //Modifier un utilisateur
    #[Route('/admin/edit/{id}', name: 'edit_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function editUser(Request $request, EntityManagerInterface $entityManager, int $id): Response{
        $repository = $entityManager->getRepository(User::class);
        $user = $repository->find($id);

        if(!$user){
            throw $this->createNotFoundException("L'utilisateur avec l'Id : $id n'existe pas");
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        //Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', "Utilisateur $id a été modifié avec succès !");
            return $this->redirectToRoute('list_user');
        }
        
        return $this->render('admin/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}