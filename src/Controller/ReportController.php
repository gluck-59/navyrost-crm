<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/report')]
final class ReportController extends AbstractController
{
    #[Route('', name: 'report_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('report/index.html.twig', [
            'title' => 'Отчеты',
        ]);
    }

    #[Route('/debitorka', name: 'report_debitorka', methods: ['GET'])]
    public function debitorka(HttpRequest $request, ReportService $reportService): Response
    {
        $user = $this->requireUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $rows = $reportService->getDebitorka($user, $isAdmin);

        if ($request->query->get('download') === 'csv') {
            return $this->createCsvResponse(
                ['Оборудование', 'Заявка', 'Заказчик', 'Сумма'],
                $rows,
                static fn (array $row): array => [
                    $row['equipment'],
                    $row['request'],
                    $row['customer'],
                    (string) $row['sum'],
                ],
                'debitorka.csv'
            );
        }

        return $this->render('report/debitorka.html.twig', [
            'title' => 'Дебиторская задолженность',
            'rows' => $rows,
        ]);
    }

    #[Route('/totalPayed', name: 'report_total_payed', methods: ['GET'])]
    public function totalPayed(HttpRequest $request, ReportService $reportService): Response
    {
        $user = $this->requireUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $rows = $reportService->getTotalPayed($user, $isAdmin);

        if ($request->query->get('download') === 'csv') {
            return $this->createCsvResponse(
                ['Заказчик', 'Оборудование', 'Заявка', 'Сумма'],
                $rows,
                static fn (array $row): array => [
                    $row['customer'],
                    $row['equipment'],
                    $row['request'],
                    (string) $row['sum'],
                ],
                'total_payed.csv'
            );
        }

        return $this->render('report/total_payed.html.twig', [
            'title' => 'Оплата по клиентам',
            'rows' => $rows,
        ]);
    }

    #[Route('/salaryByMonth', name: 'report_salary_by_month', methods: ['GET'])]
    public function salaryByMonth(HttpRequest $request, ReportService $reportService): Response
    {
        $user = $this->requireUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $rows = $reportService->getSalaryByMonth($user, $isAdmin);

        if ($request->query->get('download') === 'csv') {
            return $this->createCsvResponse(
                ['Месяц', 'Сумма'],
                $rows,
                static fn (array $row): array => [
                    $row['month'],
                    (string) $row['sum'],
                ],
                'salary_by_month.csv'
            );
        }

        return $this->render('report/salary_by_month.html.twig', [
            'title' => 'Зарплата по месяцам',
            'rows' => $rows,
        ]);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<string, mixed>> $rows
     * @param callable(array<string, mixed>): array<int, string> $rowMapper
     */
    private function createCsvResponse(array $headers, array $rows, callable $rowMapper, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($headers, $rows, $rowMapper): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $rowMapper($row), ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    private function requireUser(): User
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
