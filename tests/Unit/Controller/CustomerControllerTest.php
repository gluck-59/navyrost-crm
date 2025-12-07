<?php

namespace App\Tests\Unit\Controller;

use App\Controller\CustomerController;
use App\Entity\Customer;
use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormView;

class CustomerControllerTest extends TestCase
{
    private object $customerRepository;
    private object $userRepository;
    private object $entityManager;
    private object $formFactory;
    private object $authorizationChecker;
    private object $urlGenerator;
    private object $csrfTokenManager;
    private object $flashBag;
    private object $tokenStorage;
    private object $twig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customerRepository = $this->createStub(CustomerRepository::class);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->formFactory = $this->createStub(FormFactoryInterface::class);
        $this->authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $this->flashBag = $this->createStub(FlashBagInterface::class);
        $this->tokenStorage = $this->createStub(TokenStorageInterface::class);
        $this->twig = $this->createStub(\Twig\Environment::class);
    }

    private function createController(?User $user = null, bool $isAdmin = false): CustomerController
    {
        // Настройка моков для isGranted
        $this->authorizationChecker->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn($isAdmin);

        // Создаем контроллер
        $controller = new CustomerController();
        
        // Настраиваем мок контейнера
        $container = $this->createMock(ContainerInterface::class);
        
        // Настраиваем сервисы в контейнере
        $container->method('has')
            ->willReturnCallback(function($id) {
                return in_array($id, [
                    'security.authorization_checker',
                    'router',
                    'form.factory',
                    'twig',
                    'session.flash_bag',
                    'security.csrf.token_manager',
                    'security.token_storage',
                ]);
            });
            
        $container->method('get')
            ->willReturnMap([
                ['security.authorization_checker', 1, $this->authorizationChecker],
                ['router', 1, $this->urlGenerator],
                ['form.factory', 1, $this->formFactory],
                ['twig', 1, $this->twig],
                ['session.flash_bag', 1, $this->flashBag],
                ['security.csrf.token_manager', 1, $this->csrfTokenManager],
                ['security.token_storage', 1, $this->tokenStorage],
            ]);
            
        $controller->setContainer($container);
        
        // Устанавливаем пользователя
        if ($user !== null) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            
            $this->tokenStorage->method('getToken')->willReturn($token);
        }
        
        return $controller;
    }

    private function createUser(int $id, bool $isAdmin = false): User
    {
//        $user = $this->createMock(User::class);
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getUsername')->willReturn('testuser');
        $user->method('getRoles')->willReturn($isAdmin ? ['ROLE_ADMIN'] : ['ROLE_USER']);
        return $user;
    }

    private function createCustomer(int $id, ?User $creator = null, ?string $name = 'Test Customer'): Customer
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn($id);
        $customer->method('getName')->willReturn($name);
        $customer->method('getCreator')->willReturn($creator);
        return $customer;
    }

    public function testIndex(): void
    {
        $user = $this->createUser(1);
        $controller = $this->createController($user);
        
        $customers = [
            $this->createCustomer(1, $user, 'Customer 1'),
            $this->createCustomer(2, $user, 'Customer 2'),
        ];
        
        $this->customerRepository->expects($this->once())
            ->method('findBy')
            ->with(['creator' => $user], ['id' => 'DESC'])
            ->willReturn($customers);
        
        // Настраиваем рендеринг шаблона
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'customer/index.html.twig',
                $this->callback(function($parameters) use ($user) {
                    return $parameters['title'] === 'Клиенты' 
                        && count($parameters['customers']) === 2
                        && $parameters['is_admin'] === false;
                })
            )
            ->willReturn('rendered template');
        
        $response = $controller->index(
            new Request(),
            $this->customerRepository,
            $this->userRepository
        );
        
        $this->assertInstanceOf(Response::class, $response);
    }


    public function testShowAsOwner(): void
    {
        $user = $this->createUser(1);
        $customer = $this->createCustomer(1, $user);
        $controller = $this->createController($user);
        
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'customer/card.html.twig',
                $this->callback(function($parameters) use ($customer) {
                    return $parameters['title'] === 'Клиент'
                        && $parameters['customer'] === $customer
                        && $parameters['is_admin'] === false;
                })
            )
            ->willReturn('rendered template');
        
        $response = $controller->show($customer, $this->userRepository);
        
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testShowAsAdmin(): void
    {
        $user = $this->createUser(1, true);
        $otherUser = $this->createUser(2);
        $customer = $this->createCustomer(1, $otherUser);
        $controller = $this->createController($user, true);
        
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'customer/card.html.twig',
                $this->callback(function($parameters) use ($customer) {
                    return $parameters['is_admin'] === true;
                })
            )
            ->willReturn('rendered template');
        
        $response = $controller->show($customer, $this->userRepository);
        
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testShowAsUnauthorizedUser(): void
    {
        $this->expectException(AccessDeniedException::class);
        
        $user = $this->createUser(1);
        $otherUser = $this->createUser(2);
        $customer = $this->createCustomer(1, $otherUser);
        $controller = $this->createController($user);
        
        $controller->show($customer, $this->userRepository);
    }

    public function testAddWithValidForm(): void
    {
        $user = $this->createUser(1);
        $controller = $this->createController($user);
        
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomerType::class,
                $this->isInstanceOf(Customer::class),
                [
                    'current_user' => $user,
                    'is_admin' => false,
                ]
            )
            ->willReturn($form);
            
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Customer::class));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // Настройка моков для сессии и флеш-сообщений
        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', 'Клиент создан');
            
        $session = $this->createMock(SessionInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);
        
        $request = new Request();
        $request->setSession($session);
        
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('customer_index')
            ->willReturn('/customer');
            
        $response = $controller->add($request, $this->entityManager);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/customer', $response->getTargetUrl());
    }

    public function testAddWithInvalidForm(): void
    {
        $user = $this->createUser(1);
        $controller = $this->createController($user);

        // Create a form view stub
        $formView = $this->createStub(FormView::class);

        // Create a form stub with the necessary methods
        $form = $this->createStub(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        // Mock the form factory
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')
            ->willReturn($form);

        // Mock the token
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Mock the token storage
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        // Mock the authorization checker
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(false); // Assuming user is not admin

        // Set up the container with all required services
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')
            ->willReturnMap([
                ['form.factory', 1, $formFactory],
                ['twig', 1, $this->createMock(\Twig\Environment::class)],
                ['security.authorization_checker', 1, $authChecker],
                ['security.token_storage', 1, $tokenStorage],
                ['router', 1, $this->createMock(RouterInterface::class)],
                ['request_stack', 1, new RequestStack()]
            ]);

        // Set the container on the controller
        $controller->setContainer($container);

        // Call the method under test
        $response = $controller->add(new Request(), $this->entityManager);

        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testEditAsOwner(): void
    {
        $user = $this->createUser(1);
        $customer = $this->createCustomer(1, $user);
        $controller = $this->createController($user);
        
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                CustomerType::class, 
                $customer, 
                $this->callback(function($options) use ($user, $customer) {
                    return $options['current_customer'] === $customer 
                        && $options['current_user'] === $user 
                        && $options['is_admin'] === false;
                })
            )
            ->willReturn($form);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('success', 'Клиент обновлён');
            
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('customer_index')
            ->willReturn('/customer');
            
        $response = $controller->edit(new Request(), $customer, $this->entityManager);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/customer', $response->getTargetUrl());
    }

    public function testEditAsUnauthorizedUser(): void
    {
        $this->expectException(AccessDeniedException::class);
        
        $user = $this->createUser(1);
        $otherUser = $this->createUser(2);
        $customer = $this->createCustomer(1, $otherUser);
        $controller = $this->createController($user);
        
        $controller->edit(new Request(), $customer, $this->entityManager);
    }

    public function testDeleteWithValidToken(): void
    {
        $user = $this->createUser(1);
        $customer = $this->createCustomer(1, $user);
        $controller = $this->createController($user);
        
        $this->csrfTokenManager->method('isTokenValid')
            ->with($this->isInstanceOf(CsrfToken::class))
            ->willReturn(true);
            
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($customer);
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('success', 'Клиент удалён');
            
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('customer_index')
            ->willReturn('/customer');
            
        $request = new Request([], ['_token' => 'valid_token']);
        $response = $controller->delete($request, $customer, $this->entityManager);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/customer', $response->getTargetUrl());
    }

    public function testDeleteWithInvalidToken(): void
    {
        $user = $this->createUser(1);
        $customer = $this->createCustomer(1, $user);
        $controller = $this->createController($user);
        
        $this->csrfTokenManager->method('isTokenValid')
            ->with($this->isInstanceOf(CsrfToken::class))
            ->willReturn(false);
            
        $this->entityManager->expects($this->never())
            ->method('remove');
            
        $this->entityManager->expects($this->never())
            ->method('flush');
            
        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'Неверный CSRF токен');
            
        $request = new Request([], ['_token' => 'invalid_token']);
        $response = $controller->delete($request, $customer, $this->entityManager);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/customer', $response->getTargetUrl());
    }

    public function testDeleteAsUnauthorizedUser(): void
    {
        $this->expectException(AccessDeniedException::class);
        
        $user = $this->createUser(1);
        $otherUser = $this->createUser(2);
        $customer = $this->createCustomer(1, $otherUser);
        $controller = $this->createController($user);
        
        $request = new Request([], ['_token' => 'valid_token']);
        $controller->delete($request, $customer, $this->entityManager);
    }
}
