# JWT Login Endpoint - Step by Step Implementation

This guide shows how to create a login endpoint that authenticates users and returns JWT tokens.

## 🎯 Step 1: Create Login Input DTO

Create `src/Dto/UserLoginInput.php`:

```php
<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * User login input data
 */
class UserLoginInput
{
    /**
     * User's email address
     * 
     * @example "john.doe@example.com"
     */
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public string $email;

    /**
     * User's password
     * 
     * @example "SecurePassword123!"
     */
    #[Assert\NotBlank(message: 'Password is required')]
    public string $password;
}
```

## 🎯 Step 2: Create Login Output DTO

Create `src/Dto/UserLoginOutput.php`:

```php
<?php

namespace App\Dto;

/**
 * User login response
 */
class UserLoginOutput
{
    /**
     * JWT authentication token
     * 
     * @example "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImpvaG4uZG9lQGV4YW1wbGUuY29tIn0.example-signature"
     */
    public string $token;

    /**
     * User information
     * 
     * @example {"id": 1, "email": "john.doe@example.com", "roles": ["ROLE_USER"]}
     */
    public array $user;

    /**
     * Token expiration timestamp
     * 
     * @example 1718911200
     */
    public int $expiresAt;

    public function __construct(string $token, int $userId, string $email, array $roles, int $expiresAt)
    {
        $this->token = $token;
        $this->user = [
            'id' => $userId,
            'email' => $email,
            'roles' => $roles,
        ];
        $this->expiresAt = $expiresAt;
    }
}
```

## 🎯 Step 3: Create Login Controller

Create `src/Controller/LoginController.php`:

```php
<?php

namespace App\Controller;

use App\Entity\User;
use App\Dto\UserLoginInput;
use App\Dto\UserLoginOutput;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LoginController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function __invoke(UserLoginInput $data): UserLoginOutput
    {
        // Find user by email
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data->email]);

        if (!$user) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $data->password)) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);
        
        // Calculate expiration time (default: 1 hour)
        $expiresAt = time() + 3600;

        return new UserLoginOutput(
            $token,
            $user->getId(),
            $user->getEmail(),
            $user->getRoles(),
            $expiresAt
        );
    }
}
```

## 🎯 Step 4: Add Login Operation to User Entity

Add the login operation to your `User` entity in `src/Entity/User.php`:

```php
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\MediaType;
use App\Controller\LoginController;
use App\Dto\UserLoginInput;
use App\Dto\UserLoginOutput;

#[ApiResource(
    operations: [
        // ... existing registration operation
        new Post(
            uriTemplate: '/login',
            controller: LoginController::class,
            input: UserLoginInput::class,
            output: UserLoginOutput::class,
            security: null,
            name: 'login',
            description: 'Authenticate user and get JWT token',
            openapi: new Operation(
                tags: ['Authentication'],
                summary: 'User login',
                description: 'Authenticates a user with email and password, returns a JWT token.',
                requestBody: new RequestBody(
                    description: 'User login credentials',
                    content: new \ArrayObject([
                        'application/json' => new MediaType(
                            example: [
                                'email' => 'john.doe@example.com',
                                'password' => 'SecurePassword123!'
                            ]
                        )
                    ])
                ),
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
                ]
            ),
            deserialize: true,
            validate: true,
            write: false
        ),
    ]
)]
```

## 🎯 Step 5: Configure Security (Optional)

Update `config/packages/security.yaml` to include JWT authentication:

```yaml
security:
    # ... existing configuration
    
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
            
        api_login:
            pattern: ^/login
            stateless: true
            security: false
            
        api_register:
            pattern: ^/register
            stateless: true
            security: false
            
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
            
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Security\LoginFormAuthenticator
            logout:
                path: app_logout

    access_control:
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/docs, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

## 🎯 Step 6: Test the Login Endpoint

### Successful Login

**Request:**
```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecurePassword123!"
  }'
```

**Response (200):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "john.doe@example.com",
    "roles": ["ROLE_USER"]
  },
  "expiresAt": 1718828400
}
```

### Invalid Credentials

**Request:**
```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "wrongpassword"
  }'
```

**Response (400):**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Invalid credentials"
}
```

## 🎯 Step 7: Use JWT Token for Protected Endpoints

Once you have the token, use it in the Authorization header:

```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

## 🔧 Advanced Features (Optional)

### 1. Token Refresh Endpoint

Create a refresh token endpoint to get new tokens without re-authentication.

### 2. Rate Limiting

Add rate limiting to prevent brute force attacks:

```bash
composer require symfony/rate-limiter
```

### 3. Account Lockout

Lock accounts after multiple failed login attempts.

### 4. Login History

Track user login attempts and successful logins.

### 5. Two-Factor Authentication

Add 2FA support for enhanced security.

## 🔍 Security Best Practices

1. **Password Policy**: Enforce strong passwords
2. **Rate Limiting**: Prevent brute force attacks
3. **Account Lockout**: Lock accounts after failed attempts
4. **Secure Headers**: Add security headers
5. **Token Expiration**: Use reasonable token expiration times
6. **Refresh Tokens**: Implement token refresh mechanism
7. **Audit Logging**: Log authentication events

## 📋 Complete User Entity

Your final `User` entity should now have both registration and login operations:

```php
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            controller: RegisterController::class,
            // ... registration configuration
        ),
        new Post(
            uriTemplate: '/login',
            controller: LoginController::class,
            // ... login configuration
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ... entity implementation
}
```

Both endpoints will now be available in your API documentation at `/api/docs` with full examples and proper error handling!