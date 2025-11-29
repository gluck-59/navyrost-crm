<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Symfony\Bundle\SecurityBundle\Security;

class SecurityController extends AbstractController
{
     #[Route(path: '/', name: 'index')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($this->getUser()) {
                prettyDump($this->getUser());
            //    prettyDump($this->isGranted('ROLE_ADMIN')); // admin
            //    VarDumper::dump($this->isGranted('ROLE_ADMIN'));
            return $this->redirect('/site/main');
        } else {
//            prettyDump($error);
//            prettyDump($this->getUser());
            return $this->render('security/login.html.twig', [
                'last_username' => $lastUsername,
                'title' => 'Войдите',
                'error' => $error,
            ]);
        }
    }


    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($this->getUser()) {
            prettyDump($this->getUser());
            return $this->redirect('/customer');
        } else {
            prettyDump($this->getUser());
            return $this->render('security/login.html.twig', [
                'last_username' => $lastUsername,
                'title' => 'Войдите',
                'error' => $error,
            ]);
        }
    }


    #[Route(path: '/logout', name: 'logout')]
    public function logout(Security $security): Response
    {
        $security->logout(false);
        return $this->redirect('/');
    }

}
