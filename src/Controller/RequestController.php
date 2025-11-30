<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Equipment;
use App\Entity\Request as RequestEntity;
use App\Form\RequestCreateType;
use App\Form\RequestEditType;
use App\Repository\CustomerRepository;
use App\Repository\EquipmentRepository;
use App\Repository\PaymentRepository;
use App\Repository\RequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/request')]
final class RequestController extends AbstractController
{
    #[Route('', name: 'request_index', methods: ['GET'])]
    public function index(RequestRepository $requestRepository, PaymentRepository $paymentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $requests = $isAdmin
            ? $requestRepository->findAllOrdered()
            : $requestRepository->findForUser($user);

        $requestIds = array_map(static fn (RequestEntity $request): int => $request->getId(), $requests);
        $requestBalances = $paymentRepository->getSumByRequestIds($requestIds);

        return $this->render('request/index.html.twig', [
            'title' => 'Заявки',
            'requests' => $requests,
            'is_admin' => $isAdmin,
            'request_balances' => $requestBalances,
        ]);
    }

    #[Route('/add', name: 'request_add', methods: ['GET', 'POST'])]
    public function add(
        Request $httpRequest,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $requestEntity = new RequestEntity();

        $form = $this->createForm(RequestCreateType::class, $requestEntity, [
            'current_user' => $user,
            'is_admin' => $isAdmin,
            'equipment_api_url' => $this->generateUrl('request_equipment_by_customer', ['id' => '__ID__']),
        ]);
        $form->handleRequest($httpRequest);

        if ($form->isSubmitted()) {
            $customer = $requestEntity->getCustomer();
            $equipment = $requestEntity->getEquipment();

            if ($customer && !$isAdmin && $customer->getCreator()?->getId() !== $user->getId()) {
                $form->get('customer')->addError(new FormError('Вы не можете выбрать этого клиента.'));
            }

            if ($customer && $equipment && $equipment->getCustomer()?->getId() !== $customer->getId()) {
                $form->get('equipment')->addError(new FormError('Выберите оборудование, принадлежащее выбранному клиенту.'));
            }

            if ($equipment && !$isAdmin && $equipment->getCustomer()?->getCreator()?->getId() !== $user->getId()) {
                $form->get('equipment')->addError(new FormError('Вы не можете выбрать это оборудование.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $requestEntity->setStatus(RequestEntity::STATUS_NEW);
            $entityManager->persist($requestEntity);
            $entityManager->flush();

            $this->addFlash('success', 'Заявка создана');

            return $this->redirectToRoute('request_edit', ['id' => $requestEntity->getId()]);
        }

        return $this->render('request/add.html.twig', [
            'title' => 'Новая заявка',
            'form' => $form->createView(),
            'is_admin' => $isAdmin,
        ]);
    }

    #[Route('/{id}/edit', name: 'request_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(
        Request $httpRequest,
        RequestEntity $requestEntity,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $customerCreatorId = $requestEntity->getCustomer()?->getCreator()?->getId();
        if (!$this->isGranted('ROLE_ADMIN') && $customerCreatorId !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(RequestEditType::class, $requestEntity);
        $form->handleRequest($httpRequest);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Заявка обновлена');

            return $this->redirectToRoute('request_edit', ['id' => $requestEntity->getId()]);
        }

        $payments = $paymentRepository->findByRequest($requestEntity);
        $paymentTotals = $paymentRepository->getTotalsByRequest($requestEntity);

        return $this->render('request/edit.html.twig', [
            'title' => 'Редактирование',
            'form' => $form->createView(),
            'requestEntity' => $requestEntity,
            'payments' => $payments,
            'paymentTotals' => $paymentTotals,
        ]);
    }

    #[Route('/customer/{id}/equipment', name: 'request_equipment_by_customer', methods: ['GET'])]
    public function equipmentByCustomer(
        string $id,
        CustomerRepository $customerRepository,
        EquipmentRepository $equipmentRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if (!ctype_digit($id)) {
            return new JsonResponse(['items' => []]);
        }

        $customerId = (int) $id;
        $customer = $customerRepository->find($customerId);
        if (!$customer) {
            return new JsonResponse(['items' => []]);
        }

        if (!$this->isGranted('ROLE_ADMIN') && $customer->getCreator()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        $equipmentList = $equipmentRepository->findByCustomer($customer);

        $data = array_map(static function (Equipment $equipment): array {
            return [
                'id' => $equipment->getId(),
                'name' => $equipment->getName(),
            ];
        }, $equipmentList);

        return new JsonResponse(['items' => $data]);
    }
}
