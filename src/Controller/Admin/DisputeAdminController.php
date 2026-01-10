<?php

namespace App\Controller\Admin;

use App\Entity\Dispute;
use App\Entity\TokenTransaction;
use App\Repository\DisputeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
class DisputeAdminController extends AbstractController
{
    #[Route('/admin/disputes', name: 'admin_dispute_list', methods: ['GET'])]
    public function list(DisputeRepository $repo): Response
    {
        return $this->render('admin/dispute/list.html.twig', [
            'disputes' => $repo->findAllOrdered(200),
        ]);
    }

    #[Route('/admin/dispute/{id}', name: 'admin_dispute_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Dispute $dispute,
        EntityManagerInterface $em
    ): Response {
        // Auto-passage en "IN_REVIEW" quand un employé ouvre la fiche
        if ($dispute->getStatus() === Dispute::STATUS_OPEN) {
            $dispute->setStatus(Dispute::STATUS_IN_REVIEW);
            $em->flush();
        }

        return $this->render('admin/dispute/show.html.twig', [
            'dispute' => $dispute,
        ]);
    }

    #[Route(
        '/admin/dispute/{id}/status/{status}',
        name: 'admin_dispute_status',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function changeStatus(
        Dispute $dispute,
        string $status,
        Request $request,
        EntityManagerInterface $em,
        DisputeRepository $disputeRepo
    ): Response {
        if (!in_array($status, [
            Dispute::STATUS_IN_REVIEW,
            Dispute::STATUS_RESOLVED,
            Dispute::STATUS_REJECTED,
        ], true)) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid(
            'dispute_status_' . $dispute->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $dispute->setStatus($status);
        $em->flush();

        // ✅ Si dispute REJECTED : libérer les gains PENDING du trajet
        if ($status === Dispute::STATUS_REJECTED) {
            $trajet = $dispute->getTrajet();
            $chauffeur = $trajet->getConducteur();

            if ($trajet && $chauffeur) {
                // On libère seulement s'il n'y a PLUS aucun dispute actif sur ce trajet
                $activeCount = $disputeRepo->countActiveForTrajet($trajet->getId());

                if ($activeCount === 0) {
                    $pendingTxs = $em->getRepository(TokenTransaction::class)->findBy([
                        'user'     => $chauffeur,
                        'trajetId' => $trajet->getId(),
                        'type'     => 'PENDING',
                    ]);

                    foreach ($pendingTxs as $tx) {
                        $tx->setType('CREDIT');
                        $chauffeur->setTokens($chauffeur->getTokens() + (int) $tx->getAmount());
                    }

                    $em->flush();
                }
            }
        }

        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('admin_dispute_show', ['id' => $dispute->getId()]);
    }
}
