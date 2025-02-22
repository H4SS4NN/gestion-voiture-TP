<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicle')]
final class VehicleController extends AbstractController
{
    #[Route(name: 'app_vehicle_index', methods: ['GET'])]
    public function index(Request $request, VehicleRepository $vehicleRepository, EntityManagerInterface $entityManager): Response
    {
        $criteria = [];
        
        if ($marque = $request->query->get('marque')) {
            $criteria['marque'] = $marque;
        }
    
        if ($model = $request->query->get('model')) {
            $criteria['model'] = $model;
        }
    
        if (($availability = $request->query->get('availability')) !== null) {
            $criteria['availability'] = (bool) $availability;
        }
    
        $vehicles = $vehicleRepository->findBy($criteria);
        foreach ($vehicles as $vehicle) {
            $reservations = $entityManager->getRepository(Reservation::class)
                ->findBy(['vehicle' => $vehicle]);
    
            $isCurrentlyReserved = false;
            $endDate = null;
    
            foreach ($reservations as $reservation) {
                $now = new \DateTimeImmutable();
                if ($reservation->getStartDate() <= $now && $reservation->getEndDate() >= $now) {
                    $isCurrentlyReserved = true;
                    $endDate = $reservation->getEndDate();
                    break;
                }
            }
            if (!$vehicle->isAvailability() || $isCurrentlyReserved) {
                $vehicle->setAvailability(false);
            } else {
                $vehicle->setAvailability(true);
            }
            $vehicle->currentReservationEndDate = $endDate;
        }
    
        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }
    
    

    #[Route('/new', name: 'app_vehicle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vehicle);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/new.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    #[Route('/vehicle/{id}', name: 'app_vehicle_show', methods: ['GET'])]
    public function show(
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        $vehicle = $entityManager->getRepository(Vehicle::class)->find($id);
    
        if (!$vehicle) {
            throw $this->createNotFoundException('Véhicule introuvable.');
        }
    
        // Vérifier si l'utilisateur connecté a réservé ce véhicule
        $user = $this->getUser();
        $hasReservation = false;
    
        if ($user) {
            $reservations = $entityManager->getRepository(Reservation::class)
                ->findBy(['vehicle' => $vehicle, 'user' => $user]);
    
            $hasReservation = count($reservations) > 0;
        }
    
        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
            'hasReservation' => $hasReservation, // Transmettre l'information à la vue
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les réservations pour déterminer la disponibilité
        $reservations = $entityManager->getRepository(Reservation::class)
            ->findBy(['vehicle' => $vehicle]);
    
        $isReserved = false;
    
        foreach ($reservations as $reservation) {
            $now = new \DateTimeImmutable();
            if ($reservation->getStartDate() <= $now && $reservation->getEndDate() >= $now) {
                $vehicle->setAvailability(false); // Marquer comme indisponible
                $isReserved = true;
                break;
            }
        }
    
        // Créer le formulaire
        $form = $this->createForm(VehicleType::class, $vehicle);
    
        // Si le véhicule est réservé, supprimer la possibilité de modifier 'availability'
        if ($isReserved) {
            $form->remove('availability');
        }


        $form->remove('image');
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            $this->addFlash('success', 'Le véhicule a été modifié avec succès.');
            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('vehicle/edit.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }
    

    #[Route('/{id}', name: 'app_vehicle_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vehicle->getId(), $request->get('_token'))) {
            try {
                $entityManager->remove($vehicle);
                $entityManager->flush();
                $this->addFlash('success', 'Véhicule supprimé avec succès.');
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du véhicule.');
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            }   
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
       
    }
}
