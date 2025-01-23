<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Vehicle;
use App\Form\Comment1Type;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route(name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(Comment1Type::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }
    #[Route('/comment/new/{vehicle_id}', name: 'app_comment_new', methods: ['GET', 'POST'])]
public function newcom(
    int $vehicle_id,
    Request $request,
    EntityManagerInterface $entityManager
): Response {
    $vehicle = $entityManager->getRepository(Vehicle::class)->find($vehicle_id);

    if (!$vehicle) {
        $this->addFlash('error', 'Le véhicule sélectionné n\'existe pas.');
        return $this->redirectToRoute('app_vehicle_index');
    }

    $comment = new Comment();
    $comment->setVehicle($vehicle);
    $comment->setUser($this->getUser());
    $comment->setCreatedAt(new DateTimeImmutable());

    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
        return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle_id]);
    }

    return $this->render('comment/new.html.twig', [
        'vehicle' => $vehicle,
        'form' => $form->createView(),
    ]);
}


    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Comment1Type::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/comment/delete/{id}', name: 'app_comment_delete', methods: ['POST'])]
public function deletecom(
    int $id,
    EntityManagerInterface $entityManager,
    Request $request
): Response {
    $comment = $entityManager->getRepository(Comment::class)->find($id);

    if (!$comment) {
        $this->addFlash('error', 'Commentaire introuvable.');
        return $this->redirectToRoute('app_vehicle_index');
    }

    // Vérification des permissions (seul l'auteur ou un admin peut supprimer)
    $user = $this->getUser();
    if ($comment->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer ce commentaire.');
        return $this->redirectToRoute('app_vehicle_show', ['id' => $comment->getVehicle()->getId()]);
    }

    // Suppression du commentaire
    $entityManager->remove($comment);
    $entityManager->flush();

    $this->addFlash('success', 'Le commentaire a été supprimé avec succès.');

    return $this->redirectToRoute('app_vehicle_show', ['id' => $comment->getVehicle()->getId()]);
}

}
