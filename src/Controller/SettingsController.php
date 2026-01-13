<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/settings')]
class SettingsController extends AbstractController
{
    public function __construct(private readonly EmailVerifier $emailVerifier)
    {
    }

    #[Route('', name: 'app_settings')]
    public function index(): Response
    {
        return $this->render('settings/index.html.twig');
    }

    #[Route('/resend-verification', name: 'app_settings_resend_verification', methods: ['POST'])]
    public function resendVerification(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            $this->addFlash('success', 'Your email is already verified.');
            return $this->redirectToRoute('app_settings');
        }

        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->to((string) $user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        $this->addFlash('success', 'Verification email sent.');

        return $this->redirectToRoute('app_settings');
    }
}
