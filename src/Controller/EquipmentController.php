<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\EquipmentType;

#[Route('/equipment')]
final class EquipmentController extends AbstractController
{
    #[Route('', name: 'equipment_index', methods: ['GET'])]
    public function index(Request $request, EquipmentRepository $equipmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($isAdmin) {
            $equipment = $equipmentRepository->findBy([], ['id' => 'DESC']);
        } else {
            // Фильтрация по владельцу клиента
            $qb = $equipmentRepository->createQueryBuilder('e')
                ->join('e.customer', 'c')
                ->where('c.creator = :user')
                ->setParameter('user', $user)
                ->orderBy('e.id', 'DESC');
            $equipment = $qb->getQuery()->getResult();
        }

        return $this->render('equipment/index.html.twig', [
            'title' => 'Оборудование',
            'equipment' => $equipment,
            'is_admin' => $isAdmin,
        ]);
    }

    #[Route('/add', name: 'equipment_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $equipment = new Equipment();

        $form = $this->createForm(EquipmentType::class, $equipment, [
            'current_user' => $user,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($equipment);
            $em->flush();

            $this->addFlash('success', 'Оборудование создано');
            return $this->redirectToRoute('equipment_index');
        }

        return $this->render('equipment/add.html.twig', [
            'title' => 'Новое оборудование',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'equipment_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Equipment $equipment): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        
        // Проверка прав доступа: админ или владелец клиента
        if (!$this->isGranted('ROLE_ADMIN') && $equipment->getCustomer()?->getCreator()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $customerName = $equipment->getCustomer()?->getName();

        return $this->render('equipment/card.html.twig', [
            'title' => 'Оборудование',
            'equipment' => $equipment,
            'customer_name' => $customerName,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/{id}/edit', name: 'equipment_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, Equipment $equipment, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Проверка прав доступа: админ или владелец клиента
        if (!$this->isGranted('ROLE_ADMIN') && $equipment->getCustomer()?->getCreator()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EquipmentType::class, $equipment, [
            'current_user' => $user,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Оборудование обновлено');
            return $this->redirectToRoute('equipment_index');
        }

        return $this->render('equipment/edit.html.twig', [
            'title' => 'Редактирование оборудования',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'equipment_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Equipment $equipment, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        
        // Проверка прав доступа: админ или владелец клиента
        if (!$this->isGranted('ROLE_ADMIN') && $equipment->getCustomer()?->getCreator()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $submittedToken = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_equipment_' . $equipment->getId(), $submittedToken)) {
            $em->remove($equipment);
            $em->flush();
            $this->addFlash('success', 'Оборудование удалено');
        } else {
            $this->addFlash('error', 'Неверный CSRF токен');
        }

        return $this->redirectToRoute('equipment_index');
    }
}
