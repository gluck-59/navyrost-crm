<?php

namespace App\Tests\Unit\Controller;

use App\Controller\SiteController;
use App\Entity\Customer;
use App\Entity\Request as RequestEntity;
use App\Entity\User;
use App\Repository\RequestRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SiteControllerTest extends TestCase
{
    private MockObject $requestRepository;
    private MockObject $authorizationChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestRepository = $this->createMock(RequestRepository::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
    }
    
    private function createController($user = null)
    {
        // Create a mock for the controller with only the methods we need
        $controller = $this->getMockBuilder(SiteController::class)
            ->onlyMethods(['render', 'getUser'])
            ->getMock();
            
        // Configure the render method to return a response
        $controller->method('render')
            ->willReturnCallback(function($view, $parameters = []) {
                $response = new Response();
                $response->headers->set('X-Robots-Tag', $parameters['title'] ?? '');
                return $response;
            });
            
        // Configure the getUser method
        $controller->method('getUser')->willReturn($user);
        
        // Create a container with our mocks
        $container = new \Symfony\Component\DependencyInjection\Container();
        $container->set('security.authorization_checker', $this->authorizationChecker);
        
        // Set the container on the controller
        $controller->setContainer($container);
        
        return $controller;
    }

    public function testGuest(): void
    {
        // Create a controller with no user (not authenticated)
        $controller = $this->createController(null);
        
        // The repository should not be called for unauthenticated users
        $this->requestRepository->expects($this->never())
            ->method('findAllOrdered');
        $this->requestRepository->expects($this->never())
            ->method('findForUser');

        $response = $controller->main($this->requestRepository);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Главная', $response->headers->get('X-Robots-Tag'));
    }

    public function testUser(): void
    {
        // Create a mock user
        $user = $this->createMock(User::class);
        
        // Create a controller with a regular user
        $controller = $this->createController($user);
        
        // User doesn't have ROLE_ADMIN
        $this->authorizationChecker->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);
        
        // Create a test request
        $testRequest = new RequestEntity();
        $reflection = new \ReflectionClass($testRequest);
        
        // Set id using reflection
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($testRequest, 1);
        
        // Set name if method exists
        if (method_exists($testRequest, 'setName')) {
            $testRequest->setName('Test Request');
        }
        
        // Create and set customer if needed
        $testCustomer = new Customer();
        if (method_exists($testCustomer, 'setName')) {
            $testCustomer->setName('Test Customer');
        }
        
        if (method_exists($testRequest, 'setCustomer')) {
            $testRequest->setCustomer($testCustomer);
        }
        
        // Expect findForUser to be called once with the user
        $this->requestRepository->expects($this->once())
            ->method('findForUser')
            ->with($user)
            ->willReturn([$testRequest]);
        
        // Call the method under test
        $response = $controller->main($this->requestRepository);
        
        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Главная', $response->headers->get('X-Robots-Tag'));
    }

    public function testAdmin(): void
    {
        // Create a mock user
        $user = $this->createMock(User::class);
        
        // Create a controller with an admin user
        $controller = $this->createController($user);
        
        // User has ROLE_ADMIN
        $this->authorizationChecker->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);
        
        // Create a test request
        $testRequest = new RequestEntity();
        $reflection = new \ReflectionClass($testRequest);
        
        // Set id using reflection
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($testRequest, 1);
        
        // Set name if method exists
        if (method_exists($testRequest, 'setName')) {
            $testRequest->setName('Admin Request');
        }
        
        // Create and set customer if needed
        $testCustomer = new Customer();
        if (method_exists($testCustomer, 'setName')) {
            $testCustomer->setName('Admin Customer');
        }
        
        if (method_exists($testRequest, 'setCustomer')) {
            $testRequest->setCustomer($testCustomer);
        }
        
        // Expect findAllOrdered to be called once
        $this->requestRepository->expects($this->once())
            ->method('findAllOrdered')
            ->willReturn([$testRequest]);
        
        // Call the method under test
        $response = $controller->main($this->requestRepository);
        
        // Assert the response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Главная', $response->headers->get('X-Robots-Tag'));
    }
}
