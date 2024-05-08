<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailController extends AbstractController
{
    public function sendEmail(MailerInterface $mailer)
    {
        $email = (new Email())
            ->from('yesmine.mechmech@gmail.com')
            ->to('recipient@example.com')
            ->subject('Test Email')
            ->text('This is a test email sent from Symfony.');

        $mailer->send($email);

        return $this->redirectToRoute('homepage'); // Rediriger après l'envoi de l'email
    }
}