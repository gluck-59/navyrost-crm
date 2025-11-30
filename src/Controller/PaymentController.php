<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Request as RequestEntity;
use App\Repository\PaymentRepository;
use App\Repository\RequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use NumberFormatter;

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    private ?NumberFormatter $currencyFormatter = null;

    #[Route('', name: 'payment_create', methods: ['POST'])]
    public function create(
        Request $httpRequest,
        RequestRepository $requestRepository,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->extractData($httpRequest);

        $submittedToken = (string) ($data['_token'] ?? '');
        if (!$this->isCsrfTokenValid('payment_create', $submittedToken)) {
            return new JsonResponse(['error' => 'invalid_csrf_token'], Response::HTTP_FORBIDDEN);
        }

        $requestId = isset($data['requestId']) ? (int) $data['requestId'] : 0;
        $type = isset($data['type']) ? (int) $data['type'] : Payment::TYPE_OVERHEAD;
        $direction = isset($data['direction']) ? (int) $data['direction'] : 1;
        $sumRub = $this->toFloat($data['sum'] ?? null);

        if ($requestId <= 0 || !in_array($type, [Payment::TYPE_OVERHEAD, Payment::TYPE_WORK], true)) {
            return new JsonResponse(['error' => 'invalid_payload'], Response::HTTP_BAD_REQUEST);
        }

        /** @var RequestEntity|null $requestEntity */
        $requestEntity = $requestRepository->find($requestId);
        if (!$requestEntity) {
            return new JsonResponse(['error' => 'request_not_found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_ADMIN') && $requestEntity->getCustomer()?->getCreator()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $sum = (int) round($sumRub * 100);
        if ($direction === 0 && $sum > 0) {
            $sum = -$sum;
        } elseif ($direction === 1 && $sum < 0) {
            $sum = -$sum;
        }

        $rawNote = trim((string) ($data['note'] ?? ''));
        if ($rawNote === '') {
            $rawNote = $type === Payment::TYPE_WORK ? 'работа' : 'накл.';
        }

        $payment = new Payment();
        $payment->setRequest($requestEntity);
        $payment->setType($type);
        $payment->setSum($sum);
        $payment->setNote($rawNote);

        $entityManager->persist($payment);
        $entityManager->flush();

        $totals = $paymentRepository->getTotalsByRequest($requestEntity);

        return new JsonResponse($this->buildPaymentPayload($payment, $totals), Response::HTTP_CREATED);
    }

    #[Route('/{id}/sum', name: 'payment_update_sum', methods: ['PATCH'], requirements: ['id' => '\\d+'])]
    public function updateSum(
        Request $httpRequest,
        Payment $payment,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $this->denyPaymentAccess($payment, $user->getId());

        $data = $this->extractData($httpRequest);

        $submittedToken = (string) ($data['_token'] ?? '');
        if (!$this->isCsrfTokenValid('payment_update_' . $payment->getId(), $submittedToken)) {
            return new JsonResponse(['error' => 'invalid_csrf_token'], Response::HTTP_FORBIDDEN);
        }

        $sumRub = $this->toFloat($data['sum'] ?? null);
        $payment->setSum((int) round($sumRub * 100));

        $entityManager->flush();

        $totals = $paymentRepository->getTotalsByRequest($payment->getRequest());

        return new JsonResponse($this->buildPaymentPayload($payment, $totals));
    }

    #[Route('/{id}', name: 'payment_delete', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    public function delete(
        Request $httpRequest,
        Payment $payment,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $this->denyPaymentAccess($payment, $user->getId());

        $data = $this->extractData($httpRequest);
        $submittedToken = (string) ($data['_token'] ?? '');
        if (!$this->isCsrfTokenValid('payment_delete_' . $payment->getId(), $submittedToken)) {
            return new JsonResponse(['error' => 'invalid_csrf_token'], Response::HTTP_FORBIDDEN);
        }

        $requestEntity = $payment->getRequest();
        $entityManager->remove($payment);
        $entityManager->flush();

        $totals = $paymentRepository->getTotalsByRequest($requestEntity);

        return new JsonResponse([
            'id' => $payment->getId(),
            'totals' => $this->formatTotals($totals),
        ]);
    }

    private function extractData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        return $data;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace([',', ' '], ['.', ''], (string) $value);
    }

    private function buildPaymentPayload(Payment $payment, array $totals): array
    {
        return [
            'id' => $payment->getId(),
            'type' => $payment->getType(),
            'typeLabel' => $payment->getTypeLabel(),
            'sum' => $payment->getSum(),
            'sumRub' => $this->formatAmountRub($payment->getSum()),
            'note' => $payment->getNote(),
            'created' => $payment->getCreated()?->format('d.m.Y H:i'),
            'totals' => $this->formatTotals($totals),
            'links' => [
                'update' => $this->generateUrl('payment_update_sum', ['id' => $payment->getId()]),
                'delete' => $this->generateUrl('payment_delete', ['id' => $payment->getId()]),
            ],
            'csrf' => [
                'update' => $this->csrfTokenManager->getToken('payment_update_' . $payment->getId())->getValue(),
                'delete' => $this->csrfTokenManager->getToken('payment_delete_' . $payment->getId())->getValue(),
            ],
        ];
    }

    private function formatTotals(array $totals): array
    {
        $income = (int) ($totals['income'] ?? 0);
        $expense = (int) ($totals['expense'] ?? 0);

        return [
            'income' => [
                'raw' => $income,
                'rub' => $this->formatAmountRub($income),
            ],
            'expense' => [
                'raw' => $expense,
                'rub' => $this->formatAmountRub($expense),
            ],
        ];
    }

    private function formatAmountRub(int $amount): string
    {
        $formatter = $this->getCurrencyFormatter();
        $formatted = $formatter->formatCurrency($amount / 100, 'RUB');

        if ($formatted === false) {
            return number_format($amount / 100, 0, ',', ' ');
        }

        return $formatted;
    }

    private function denyPaymentAccess(Payment $payment, int $userId): void
    {
        $requestEntity = $payment->getRequest();
        if (!$requestEntity) {
            throw $this->createAccessDeniedException();
        }

        $creatorId = $requestEntity->getCustomer()?->getCreator()?->getId();
        if (!$this->isGranted('ROLE_ADMIN') && $creatorId !== $userId) {
            throw $this->createAccessDeniedException();
        }
    }

    private function getCurrencyFormatter(): NumberFormatter
    {
        if ($this->currencyFormatter instanceof NumberFormatter) {
            return $this->currencyFormatter;
        }

        $formatter = new NumberFormatter('ru_RU', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);

        return $this->currencyFormatter = $formatter;
    }
}
