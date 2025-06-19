<?php

namespace App\Controller\Security;

use App\Dto\registrationDto\UserRegistrationInput;
use App\Dto\registrationDto\UserRegistrationOutput;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    public function __invoke(Request $request, UserRegistrationInput $input): JsonResponse
    {
        // Parse request content
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON payload', 400);
        }

        $input->email = $data['email'] ?? null;
        $input->password = $data['password'] ?? null;

        // Validate the input DTO
        $errors = $this->validator->validate($input);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException(implode(', ', $errorMessages), 400);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(Users::class)
            ->findOneBy(['email' => $input->email]);

        if ($existingUser) {
            throw new ConflictHttpException('User with this email already exists');
        }

        // Create new user
        $user = new Users();
        $user->setEmail($input->email);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $input->password);
        $user->setPassword($hashedPassword);

        // Persist the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        // Create output DTO
        $output = new UserRegistrationOutput($token, $user->getId(), $user->getEmail());

        // Serialize output DTO to JSON
        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}