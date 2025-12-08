<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request; // 🔥 OUBLIÉ
use Symfony\Component\Routing\Annotation\Route;

class AimlabController extends AbstractController
{
    #[Route('/aimlab/top3', name: 'aimlab_top3', methods: ['GET'])]
    public function top3(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.aimlabBestAvg IS NOT NULL')
            ->orderBy('u.aimlabBestAvg', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        $data = [];

        foreach ($users as $u) {
            $data[] = [
                'prenom' => $u->getPrenom(),
                'nom_initial' => strtoupper(substr($u->getNom(), 0, 1)),
                'average' => round($u->getAimlabBestAvg()),
            ];
        }

        return $this->json($data, 200, [], [
    'json_encode_options' => JSON_UNESCAPED_UNICODE
]);

    }


    #[Route('/aimlab/submit', name: 'aimlab_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'not_logged'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['average'])) {
            return new JsonResponse(['error' => 'invalid'], 400);
        }

        $avg = floatval($data['average']);

        if ($user->getAimlabBestAvg() === null || $avg < $user->getAimlabBestAvg()) {
            $user->setAimlabBestAvg($avg);
            $em->flush();
        }

        return new JsonResponse(['saved' => true, 'avg' => $avg]);
    }
}
