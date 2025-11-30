<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
//use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\CustomerType;

#[Route('/customer')]
final class CustomerController extends AbstractController
{
    #[Route('', name: 'customer_index', methods: ['GET'])]
    public function index(Request $request, CustomerRepository $customerRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($isAdmin) {
            $customers = $customerRepository->findBy([], ['id' => 'DESC']);
        } else {
            // теперь фильтрация по объекту User
            $customers = $customerRepository->findBy(['creator' => $user], ['id' => 'DESC']);
        }

        return $this->render('customer/index.html.twig', [
            'title' => 'Клиенты',
            'customers' => $customers,
            'is_admin' => $isAdmin,
        ]);
    }

    #[Route('/add', name: 'customer_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $customer = new Customer();

        $form = $this->createForm(CustomerType::class, $customer, [
            'current_user' => $user,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer->setCreator($user);
            $em->persist($customer);
            $em->flush();

            $this->addFlash('success', 'Клиент создан');
            return $this->redirectToRoute('customer_index');
        }

        return $this->render('customer/add.html.twig', [
            'title' => 'Новый клиент',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'customer_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Customer $customer, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isGranted('ROLE_ADMIN') && $customer->getCreator()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $creatorUsername = null;
        if ($this->isGranted('ROLE_ADMIN')) {
            $creatorUsername = $customer->getCreator()?->getUsername();
        }

        return $this->render('customer/card.html.twig', [
            'title' => 'Клиент',
            'customer' => $customer,
            'creator_username' => $creatorUsername,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/{id}/edit', name: 'customer_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isGranted('ROLE_ADMIN') && $customer->getCreator()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CustomerType::class, $customer, [
            'current_customer' => $customer,
            'current_user' => $user,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Клиент обновлён');
            return $this->redirectToRoute('customer_index');
        }

        return $this->render('customer/edit.html.twig', [
            'title' => 'Редактирование клиента',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'customer_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isGranted('ROLE_ADMIN') && $customer->getCreator()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $submittedToken = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_customer_' . $customer->getId(), $submittedToken)) {
            $em->remove($customer);
            $em->flush();
            $this->addFlash('success', 'Клиент удалён');
        } else {
            $this->addFlash('error', 'Неверный CSRF токен');
        }

        return $this->redirectToRoute('customer_index');
    }
}
