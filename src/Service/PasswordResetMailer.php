<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordResetMailer
{
    private MailerInterface $mailer;
    private string $fromEmail;
    private string $frontendUrl;

    public function __construct(
        MailerInterface $mailer,
        string $fromEmail = 'noreply@resources.com',
        string $frontendUrl = 'http://localhost:3000'
    ) {
        $this->mailer = $mailer;
        $this->fromEmail = $fromEmail;
        $this->frontendUrl = rtrim($frontendUrl, '/');
    }

    public function sendPasswordResetEmail(string $userEmail, string $resetToken): void
    {
        $resetUrl = $this->frontendUrl . '/reinitialiser-mot-de-passe?token=' . urlencode($resetToken);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($userEmail)
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->getHtmlTemplate($resetUrl))
            ->text($this->getTextTemplate($resetUrl));

        $this->mailer->send($email);
    }

    private function getHtmlTemplate(string $resetUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Réinitialisation de mot de passe</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h1 style='color: #2c3e50;'>Réinitialisation de votre mot de passe</h1>

                <p>Bonjour,</p>

                <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte sur la plateforme Resources Relationnelles.</p>

                <p>Pour définir un nouveau mot de passe, cliquez sur le lien ci-dessous :</p>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetUrl}'
                       style='background-color: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Réinitialiser mon mot de passe
                    </a>
                </p>

                <p><strong>Ce lien expire dans 1 heure.</strong></p>

                <p>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>

                <p>Cordialement,<br>
                L'équipe Resources Relationnelles</p>

                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

                <p style='font-size: 12px; color: #666;'>
                    Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                    <a href='{$resetUrl}'>{$resetUrl}</a>
                </p>
            </div>
        </body>
        </html>
        ";
    }

    private function getTextTemplate(string $resetUrl): string
    {
        return "
        RÉINITIALISATION DE VOTRE MOT DE PASSE

        Bonjour,

        Vous avez demandé la réinitialisation de votre mot de passe pour votre compte sur la plateforme Resources Relationnelles.

        Pour définir un nouveau mot de passe, cliquez sur ce lien :
        {$resetUrl}

        Ce lien expire dans 1 heure.

        Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.

        Cordialement,
        L'équipe Resources Relationnelles

        ---
        Si le lien ne fonctionne pas, copiez-le dans votre navigateur.
        ";
    }
}
