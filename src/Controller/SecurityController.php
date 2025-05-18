<?php

namespace App\Controller;

use App\Form\ResetPasswordForm;
use App\Form\ResetPasswordRequestForm;
use App\Repository\UsersRepository;
use App\Services\JwtService;
use App\Services\SendEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/forgottenPassword', name: 'forgotten_password')]
    public function forgotenPassword(
        Request $request,
        UsersRepository $usersRepository,
        JwtService $jwt, SendEmailService $sendEmail
    ): Response
    {
        $form = $this->createForm(ResetPasswordRequestForm::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // find the user by email
            $user = $usersRepository->findOneBy(['email' => $form->get('email')->getData()]);

            // check if exist
            if($user){
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

                // generated a URL to reset_pass
                $url = $this->generateUrl('reset_pass', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                // send Email
                $sendEmail->sendEmail(
                    'no-reply@symfoblog.fg',
                    $user->getEmail(),
                    'Reset Password',
                    'passwordReset',
                    compact('user', 'url')
                );
                $this->addFlash('success', 'Check your email for reset password link');
                return $this->redirectToRoute('app_login');
            }
            $this->addFlash('danger', 'Some thing went wrong');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('security/reset_password.html.twig', [
            'resetPasswordForm' => $form->createView(),
        ]);
    }

    #[Route('/resetPass/{token}', name: 'reset_pass')]
    public function resetPass(
        $token, JwtService $jwt,
        UsersRepository $usersRepository,
        EntityManagerInterface $em,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
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
            if($user){
                $form = $this->createForm(ResetPasswordForm::class, $user);
                $form->handleRequest($request);
                if($form->isSubmitted() && $form->isValid()){
                    $user->setPassword($userPasswordHasher->hashPassword(
                        $user, $form->get('password')->getData()
                    ));
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('success', 'Your password has been reset!');
                    return $this->redirectToRoute('app_login');
                }
                return $this->render('security/passwords.html.twig', [
                    'resetForm' => $form->createView(),
                ]);
            }

        }
        $this->addFlash('danger', 'token is invalid or expired!');
        return $this->redirectToRoute('app_login');
    }
}
