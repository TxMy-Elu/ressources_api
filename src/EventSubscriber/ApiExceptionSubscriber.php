<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
        } elseif ($exception instanceof AccessDeniedException) {
            $status = Response::HTTP_FORBIDDEN;
        } elseif ($exception instanceof AuthenticationException) {
            $status = Response::HTTP_UNAUTHORIZED;
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $message = match (true) {
            $status === 401 => 'Authentification requise',
            $status === 403 => 'Accès refusé',
            $status === 404 => 'Ressource introuvable',
            $status === 422 => 'Données invalides',
            $status >= 500 => 'Erreur serveur : ' . $exception->getMessage(),
            default => $exception->getMessage() ?: Response::$statusTexts[$status] ?? 'Erreur',
        };

        $response = new JsonResponse(
            ['error' => $message, 'code' => $status],
            $status
        );

        $event->setResponse($response);
    }
}
