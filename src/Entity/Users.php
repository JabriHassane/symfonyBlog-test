<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use App\Controller\Security\LoginController;
use App\Controller\Security\RegisterController;
use App\Dto\loginDto\UserLoginInput;
use App\Dto\loginDto\UserLoginOutput;
use App\Dto\registrationDto\UserRegistrationInput;
use App\Dto\registrationDto\UserRegistrationOutput;
use App\Repository\UsersRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

use function PHPSTORM_META\type;

/* ------API resources------ */

#[ApiResource(
    operations: [
        // Registration New User
        new Post(
            uriTemplate: '/register',
            controller: RegisterController::class,
            openapi: new Operation(
                tags: ['Authentication'],
                responses: [
                    '201' => new Response(
                        description: 'User registered successfully',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                example: [
                                    'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImpvaG4uZG9lQGV4YW1wbGUuY29tIiwiaWF0IjoxNzE4ODI0ODAwLCJleHAiOjE3MTg5MTEyMDB9.example-signature',
                                    'user' => [
                                        'id' => 1,
                                        'email' => 'john.doe@example.com'
                                    ]
                                ]
                            )
                        ])
                    ),
                    '400' => new Response(
                        description: 'Validation error',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                example: [
                                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                                    'title' => 'An error occurred',
                                    'detail' => 'email: This value is not a valid email address.',
                                    'violations' => [
                                        [
                                            'propertyPath' => 'email',
                                            'message' => 'This value is not a valid email address.',
                                            'code' => 'bd79c0ab-ddba-46cc-a703-a7a4b08de310'
                                        ]
                                    ]
                                ]
                            )
                        ])
                    ),
                    '409' => new Response(
                        description: 'User already exists',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                example: [
                                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                                    'title' => 'An error occurred',
                                    'detail' => 'User with this email already exists'
                                ]
                            )
                        ])
                    )
                ],
                summary: 'Register a new user',
                description: 'Creates a new user account with email and password, returns a JWT token for authentication.',
                requestBody: new RequestBody(
                    description: 'User registration data',
                    content: new \ArrayObject([
                        'application/json' => new MediaType(
                            example: [
                                'email' => 'john.doe@example.com',
                                'password' => 'SecurePassword123!'
                            ]
                        )
                    ])
                )
            ),
            description: 'Register a new user account',
            security: null,  // null for public access
            input: UserRegistrationInput::class,
            output: UserRegistrationOutput::class,
            deserialize: true,
            validate: true,
            write: false,
            name: 'register',
        ),

        // Login JWT
        new Post(
            uriTemplate: '/login',
            controller: LoginController::class,
            openapi: new Operation(
                tags: ['Authentication'],
                responses: [
                    '200' => new Response(
                        description: 'Login successful',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                example: [
                                    'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImpvaG4uZG9lQGV4YW1wbGUuY29tIiwiaWF0IjoxNzE4ODI0ODAwLCJleHAiOjE3MTg4Mjg0MDB9.example-signature',
                                    'user' => [
                                        'id' => 1,
                                        'email' => 'john.doe@example.com',
                                        'roles' => ['ROLE_USER']
                                    ],
                                    'expiresAt' => 1718828400
                                ]
                            )
                        ])
                    ),
                    '400' => new Response(
                        description: 'Invalid credentials',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                example: [
                                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                                    'title' => 'An error occurred',
                                    'detail' => 'Invalid credentials'
                                ]
                            )
                        ])
                    ),
                    '422' => new Response(
                        description: 'Validation error',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                example: [
                                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                                    'title' => 'An error occurred',
                                    'detail' => 'email: This value is not a valid email address.',
                                    'violations' => [
                                        [
                                            'propertyPath' => 'email',
                                            'message' => 'This value is not a valid email address.',
                                            'code' => 'bd79c0ab-ddba-46cc-a703-a7a4b08de310'
                                        ]
                                    ]
                                ]
                            )
                        ])
                    )
                ],
                summary: 'User login',
                description: 'Authenticates a user with email and password, returns a JWT token.',
                requestBody: new RequestBody(
                    description: 'User login credentials',
                    content: new \ArrayObject([
                        'application/json' => new MediaType(
                            example: [
                                'email'=>'testmail@exp.com',
                                'password'=>'12345!'
                            ]
                        )
                    ])
                )
            ),
            description: 'Authenticate user and get JWT token',
            security: null,
            input: UserLoginInput::class,
            output: UserLoginOutput::class,
            deserialize: true,
            validate: true,
            write: false,
            name: 'login'
        )
    ]
)]

/* ------------------------ */

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['user:read'])]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[Groups(['user:write'])]
    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
