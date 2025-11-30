<?php
// src/Controller/SiteController.php
namespace App\Controller;

use App\Entity\Request as RequestEntity;
use App\Repository\RequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SiteController extends AbstractController
{
    #[Route('/site/main', name: 'main')]
    public function main(RequestRepository $requestRepository): Response
    {
        $user = $this->getUser();

        $requests = [];
        if ($user) {
            $requests = $this->isGranted('ROLE_ADMIN')
                ? $requestRepository->findAllOrdered()
                : $requestRepository->findForUser($user);
        }

        $paymentRequests = array_map(
            static fn (RequestEntity $request): array => [
                'id' => $request->getId(),
                'label' => sprintf('%d. %s', $request->getId(), (string) $request->getName()),
                'customer' => (string) ($request->getCustomer()?->getName() ?? ''),
            ],
            $requests
        );

        return $this->render('site/main.html.twig', [
            'title' => 'Главная',
            'paymentRequests' => $paymentRequests
        ]);
    }

    #[Route('/site/pi', name: 'phpinfo')]
    public function pi(): Response
    {
        ob_start();
        phpinfo();
        $info = ob_get_clean();

        return new Response($info);
    }
}
