<?php

declare(strict_types=1);

namespace App\Infrastructure\Email;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Port do ProvedorMensagemEmail (Glow) usando Symfony Mailer + Resend.
 */
final class ResendEmailGateway
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly string $from,
    ) {
    }

    public function send(string $to, string $subject, string $html, ?string $idempotencyKey = null): bool
    {
        if (!$this->enabled) {
            $this->logger->info('Envio de e-mail desabilitado (MAIL_ENABLED=false).', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return false;
        }

        if ($this->from === '') {
            $this->logger->warning('MAIL_FROM não configurado.');

            return false;
        }

        try {
            $email = (new Email())
                ->from($this->from)
                ->to($to)
                ->subject($subject)
                ->html($html);

            if ($idempotencyKey !== null) {
                $email->getHeaders()->addTextHeader('X-Entity-Ref-ID', $idempotencyKey);
            }

            $this->mailer->send($email);

            $this->logger->info('E-mail enviado via Resend.', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('Falha ao enviar e-mail via Resend.', [
                'to' => $to,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
