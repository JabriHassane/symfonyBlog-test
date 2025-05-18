<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationForm;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Services\JwtService;
use App\Services\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{

    /*
    #[Route('/test-email', name: 'test_email')]
    public function testEmail(SendEmailService $sendEmail): Response
    {
        $sendEmail->sendEmail(
            'test@example.com',
            'recipient@example.com',
            'Test Email',
            'register',  // Make sure this template exists
            ['user' => ['name' => 'Test User'], 'token' => 'test-token']
        );

        return new Response('Email sent! Check MailHog at http://localhost:8025');
    }
*/
    #[Route('/register', name: 'app_register')]
    public function register(Request $request,
                             UserPasswordHasherInterface $userPasswordHasher,
                             Security $security, EntityManagerInterface $entityManager,
                            JwtService $jwt, SendEmailService $sendEmail
    ): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email
            // Generate Token
            // Header
            $header = [
                'type'=>'JWT',
                'alg'=>'HS256'
            ];
            // Payload
            $payload = [
                'userId'=> $user->getId(),
            ];
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtSecret'));

            // send Email
            $sendEmail->sendEmail(
                'no-reply@symfoblog.fg',
                $user->getEmail(),
                'Account Activation',
                'register',
                compact('user', 'token')
            );
            return $security->login($user, UsersAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/{token}', name: 'verify_user')]
    public function verificateUser(
        $token, JwtService $jwt,
        UsersRepository $usersRepository, EntityManagerInterface $em
    ): Response
    {
        // verify if the token is correct
        if ($jwt->isValid($token) && !$jwt->isExpired($token)
            && $jwt->check($token, $this->getParameter('app.jwtSecret'))
        ) {
            // the token is valide
            $payload = $jwt->getPayload($token);

            // get the current user
            $user = $usersRepository->find($payload['userId']);

            // verify if the user is not already activated
            if($user && !$user->isVerified()) {
                $user->setIsVerified(true);
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Your account has been activated!');
                return $this->redirectToRoute('app_main');
            }
        }
        $this->addFlash('danger', 'token is invalid or expired!');
        return $this->redirectToRoute('app_login');
    }
}
