<?php

namespace App\Service;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class SimpleEmailService
{
    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;
    private MailerInterface $mailer;
    
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger,
        MailerInterface $mailer
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }
    
    public function sendVerificationEmail(string $to, string $token, string $username): void
    {
        $this->logger->info('=== SERVICE EMAIL DEBUG ===');
        $this->logger->info('Destinataire: ' . $to);
        $this->logger->info('Token reçu du controller: ' . $token);
        $this->logger->info('Longueur token reçu: ' . strlen($token));
        
        $this->logger->info('Envoi email de vérification', ['to' => $to]);
        
        // ⭐⭐ CORRECTION ICI : Utilisez APP_URL ou localhost par défaut
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $verificationUrl = $baseUrl . '/verify-email/' . $token;
        
        $this->logger->info('URL générée: ' . $verificationUrl);
        
        // Crée l'email
        $email = (new Email())
            ->from('bouchikhidoha2@gmail.com')
            ->to($to)
            ->subject('Vérification email - CLUBGI')
            ->html("
                <h1>Vérification email</h1>
                <p>Bonjour {$username},</p>
                <p>Merci pour votre inscription !</p>
                <p>Veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email :</p>
                <p><a href='{$verificationUrl}' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                    Vérifier mon email
                </a></p>
                <p>Ou copiez-collez ce lien dans votre navigateur :<br>
                <code>{$verificationUrl}</code></p>
                <p>Ce lien expirera dans 24 heures.</p>
                <hr>
                <p><small>Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.</small></p>
            ");
        
        // Envoie l'email
        $this->mailer->send($email);
        
        $this->logger->info('Email envoyé avec succès', ['to' => $to]);
    }
}