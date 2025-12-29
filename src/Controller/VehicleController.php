<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VehicleController extends AbstractController
{
    // ----------------------------------------------------------
    // 🚗 LISTE DES VÉHICULES DE L'UTILISATEUR
    // ----------------------------------------------------------
    #[Route('/profil/vehicules', name: 'app_vehicles')]
    public function index(VehicleRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }

        // méthode findByUser() mais on filtre bien sur v.owner dans le repo
        $vehicles = $repo->findByUser($user);

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }

    // ----------------------------------------------------------
    // ➕ AJOUTER UN VÉHICULE
    // ----------------------------------------------------------
    #[Route('/profil/vehicule/nouveau', name: 'vehicle_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }

        $vehicle = new Vehicle();
        $vehicle->setOwner($user);   // ✅ ICI

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté avec succès !');
            return $this->redirectToRoute('app_vehicles');
        }

        return $this->render('vehicle/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ----------------------------------------------------------
    // ✏️ MODIFIER UN VÉHICULE
    // ----------------------------------------------------------
    #[Route('/profil/vehicule/{id}/edit', name: 'vehicle_edit')]
    public function edit(
        Vehicle $vehicle,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        if ($vehicle->getOwner() !== $this->getUser()) { // ✅ ICI
            throw $this->createAccessDeniedException("Tu n’as pas la permission de modifier ce véhicule.");
        }

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();
            $this->addFlash('success', 'Modifications enregistrées !');

            return $this->redirectToRoute('app_vehicles');
        }

        return $this->render('vehicle/edit.html.twig', [
            'vehicle' => $vehicle,
            'form'    => $form->createView(),
        ]);
    }

    // ----------------------------------------------------------
    // 🗑 SUPPRIMER UN VÉHICULE
    // ----------------------------------------------------------
    #[Route('/profil/vehicule/{id}/delete', name: 'vehicle_delete')]
    public function delete(
        Vehicle $vehicle,
        EntityManagerInterface $em
    ): Response {

        if ($vehicle->getOwner() !== $this->getUser()) { // ✅ ICI
            throw $this->createAccessDeniedException("Tu n’as pas la permission de supprimer ce véhicule.");
        }

        $em->remove($vehicle);
        $em->flush();

        $this->addFlash('success', 'Véhicule supprimé.');
        return $this->redirectToRoute('app_vehicles');
    }
}
