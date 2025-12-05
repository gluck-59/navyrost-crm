<?php

namespace App\Tests\Unit\Controller;

use App\Controller\SecurityController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityControllerTest extends TestCase
{
    public function testGuest(): void
    {
        // Создаем мок для контроллера
        $controller = $this->getMockBuilder(SecurityController::class)
            ->onlyMethods(['getUser', 'render'])
            ->getMock();

        // Настраиваем мок
        $controller->method('getUser')->willReturn(null);

        // Создаем мок исключения
        $exception = $this->createMock('Symfony\Component\Security\Core\Exception\AuthenticationException');

        // Настраиваем ожидаемый вызов render с правильными параметрами
        $controller->expects($this->once())
            ->method('render')
            ->with(
                'security/login.html.twig',
                [
                    'last_username' => 'test@example.com',
                    'error' => $exception,
                    'title' => 'Войдите'
                ]
            )
            ->willReturn(new Response('rendered template'));

        // Настраиваем AuthenticationUtils, чтобы он возвращал наше исключение
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);
        $authenticationUtils->method('getLastUsername')->willReturn('test@example.com');
        $authenticationUtils->method('getLastAuthenticationError')->willReturn($exception);

        // Вызываем метод и проверяем результат
        $response = $controller->login($authenticationUtils);

        // Убеждаемся, что метод вернул объект Response
        // Проверяем, что произошел редирект на /customer
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered template', $response->getContent());
    }



    /**
     * Этот тест проверяет сценарий, когда аутентифицированный пользователь заходит на страницу входа.
     * В этом случае контроллер должен перенаправить его на страницу /customer.
     *
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAuthUser(): void
    {
        // 1. Создаем мок аутентифицированного пользователя
        $user = $this->createMock(UserInterface::class);

        // 2. Создаем мок контроллера и подменяем метод getUser()
        $controller = $this->getMockBuilder(SecurityController::class)
            ->onlyMethods(['getUser'])
            ->getMock();
        $controller->method('getUser')->willReturn($user);

        // 3. Устанавливаем контейнер (необходим для работы контроллера)
        $container = new Container();
        $controller->setContainer($container);

        // 4. Создаем мок для AuthenticationUtils
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);

        // 5. Вызываем тестируемый метод
        $response = $controller->login($authenticationUtils);

        // 6. Проверяем результат
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('/customer', $response->getTargetUrl());
    }
}
