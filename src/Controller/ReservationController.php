<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;


#[Route('/reservation')]
final class ReservationController extends AbstractController
{
 
    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        // Vérifier si l'utilisateur est administrateur
        if ($this->isGranted('ROLE_ADMIN')) {
            // L'utilisateur est administrateur : on affiche toutes les réservations
            $reservations = $reservationRepository->findAll();
        } else {
            // L'utilisateur est un client : on affiche uniquement ses réservations
            $user = $this->getUser();
            $reservations = $reservationRepository->findBy(['user' => $user]);
        }
    
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }
    

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le véhicule de la réservation
            $vehicle = $reservation->getVehicle();
    
            // Vérifier la disponibilité
            if (!$vehicle->isAvailability()) {
                $this->addFlash('error', 'Ce véhicule est déjà réservé.');
                return $this->redirectToRoute('app_reservation_new');
            }
            // Calcul du prix total
            $days = $reservation->getEndDate()->diff($reservation->getStartDate())->days;
            $totalPrice = $days * $vehicle->getPricePerDay();
            
            // Appliquer une remise de 10% si le prix dépasse 400 euros
            if ($totalPrice > 400) {
                $totalPrice *= 0.9;
            }
            
            $reservation->setTotalPrice($totalPrice);
    
            $entityManager->persist($reservation);
            $entityManager->flush();
    
            $this->addFlash('success', 'La réservation a été créée avec succès.');
    
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }


    #[Route('/reservation/add/{vehicle_id}', name: 'app_reservation_add', methods: ['GET', 'POST'])]
public function addReservation(
    int $vehicle_id,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    // Récupérer le véhicule
    $vehicle = $entityManager->getRepository(Vehicle::class)->find($vehicle_id);

    // Vérifier si le véhicule existe
    if (!$vehicle) {
        $this->addFlash('error', 'Le véhicule sélectionné n\'existe pas.');
        return $this->redirectToRoute('app_vehicle_index');
    }

    // Récupérer les réservations existantes pour ce véhicule
    $reservations = $entityManager->getRepository(Reservation::class)
        ->findBy(['vehicle' => $vehicle]);

    // Construire un tableau de périodes indisponibles
    $unavailableDates = [];
    foreach ($reservations as $reservation) {
        $unavailableDates[] = [
            'start' => $reservation->getStartDate()->format('Y-m-d'),
            'end' => $reservation->getEndDate()->format('Y-m-d'),
        ];
    }

    // Créer une nouvelle réservation
    $reservation = new Reservation();
    $reservation->setVehicle($vehicle);
    $reservation->setUser($this->getUser()); // Associer l'utilisateur connecté
    $reservation->setStatus('validé'); // Statut par défaut

    // Formulaire pour sélectionner les dates
    $form = $this->createFormBuilder($reservation)
        ->add('startDate', DateTimeType::class, [
            'label' => 'Date de début',
            'widget' => 'single_text',
        ])
        ->add('endDate', DateTimeType::class, [
            'label' => 'Date de fin',
            'widget' => 'single_text',
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Vérifier si les dates ne se chevauchent pas
        foreach ($reservations as $existingReservation) {
            if (
                $reservation->getStartDate() <= $existingReservation->getEndDate() &&
                $reservation->getEndDate() >= $existingReservation->getStartDate()
            ) {
                $this->addFlash('error', 'Ces dates chevauchent une réservation existante.');
                return $this->redirectToRoute('app_reservation_add', ['vehicle_id' => $vehicle_id]);
            }
        }

        // Vérifier que la date de fin est après la date de début
        if ($reservation->getStartDate() >= $reservation->getEndDate()) {
            $this->addFlash('error', 'La date de fin doit être ultérieure à la date de début.');
            return $this->redirectToRoute('app_reservation_add', ['vehicle_id' => $vehicle_id]);
        }

        // Calculer le prix total
        $days = $reservation->getEndDate()->diff($reservation->getStartDate())->days + 1;
        $totalPrice = $days * $vehicle->getPricePerDay();
        $reservation->setTotalPrice($totalPrice);

        // Sauvegarder la réservation
        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été créée avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    }

    return $this->render('reservation/add.html.twig', [
        'vehicle' => $vehicle,
        'form' => $form->createView(),
        'unavailableDates' => $unavailableDates,
    ]);
}

    

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Sauvegarder l'ancien véhicule et les anciennes dates pour comparaison
        $originalVehicle = $reservation->getVehicle();
        $originalStartDate = $reservation->getStartDate();
        $originalEndDate = $reservation->getEndDate();
    
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Si le véhicule a changé, remettre l'ancien véhicule comme disponible
            if ($originalVehicle !== $reservation->getVehicle()) {
                $originalVehicle->setAvailability(true);
    
                // Marquer le nouveau véhicule comme indisponible
                $reservation->getVehicle()->setAvailability(false);
            }
    
            // Si les dates ont changé, recalculer le prix total
            if ($originalStartDate !== $reservation->getStartDate() || $originalEndDate !== $reservation->getEndDate()) {
                $days = $reservation->getEndDate()->diff($reservation->getStartDate())->days;
                $totalPrice = $days * $reservation->getVehicle()->getPricePerDay();
                $reservation->setTotalPrice($totalPrice);
            }
    
            // Sauvegarder les modifications
            $entityManager->flush();
    
            $this->addFlash('success', 'La réservation a été mise à jour avec succès.');
    
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }
    

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->get('_token'))) {
            // Rendre le véhicule disponible
            $vehicle = $reservation->getVehicle();
            $vehicle->setAvailability(true);
    
            $entityManager->remove($reservation);
            $entityManager->flush();
    
            $this->addFlash('success', 'La réservation a été annulée et le véhicule est maintenant disponible.');
        }
    
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
    
}
