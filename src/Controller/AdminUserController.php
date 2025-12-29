<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserEditType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users', name: 'admin_users_')]
class AdminUserController extends AbstractController
{
    // =========================================================
    // 📋 LISTE DES UTILISATEURS + RECHERCHE + FILTRE ADMINS
    // =========================================================
    #[Route('/', name: 'index')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = $request->query->get('search', '');
        $role   = $request->query->get('role');

        $qb = $em->getRepository(User::class)
            ->createQueryBuilder('u');

        if ($search !== '') {
            $qb
                ->andWhere('u.email LIKE :search OR u.prenom LIKE :search OR u.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($role === 'admin') {
            // roles est un champ JSON
            $qb
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%');
        }

        $qb->orderBy('u.id', 'ASC');

        $users = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/users.html.twig', [
            'users'  => $users,
            'search' => $search,
            'role'   => $role,
        ]);
    }

    // =========================================================
    // ✏️ MODIFIER UN UTILISATEUR (AVEC GESTION ROLE ADMIN)
    // =========================================================
    #[Route('/edit/{id}', name: 'edit')]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $form = $this->createForm(AdminUserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🔐 Sécurité : ne pas se retirer soi-même le rôle admin
            if (
                $user === $this->getUser()
                && !$form->get('isAdmin')->getData()
            ) {
                $this->addFlash(
                    'danger',
                    'Tu ne peux pas te retirer tes propres droits administrateur.'
                );

                return $this->redirectToRoute('admin_users_edit', [
                    'id' => $user->getId(),
                ]);
            }

            // 👑 Gestion du rôle admin
            $isAdmin = $form->get('isAdmin')->getData();
            $roles   = $user->getRoles();

            if ($isAdmin && !in_array('ROLE_ADMIN', $roles, true)) {
                $roles[] = 'ROLE_ADMIN';
            }

            if (!$isAdmin && in_array('ROLE_ADMIN', $roles, true)) {
                $roles = array_filter(
                    $roles,
                    fn ($role) => $role !== 'ROLE_ADMIN'
                );
            }

            $user->setRoles($roles);

            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour ✔️');

            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    // =========================================================
    // 🗑️ SUPPRIMER UN UTILISATEUR
    // =========================================================
    #[Route('/delete/{id}', name: 'delete')]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé ✔️');

        return $this->redirectToRoute('admin_users_index');
    }
}
