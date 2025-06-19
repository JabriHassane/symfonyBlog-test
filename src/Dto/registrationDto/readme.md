# User Registration Endpoint

This documentation describes the implementation of a user registration endpoint using **Symfony 7.3** and **API Platform 4** with JWT authentication.

## 📋 Overview

The registration endpoint allows new users to create accounts by providing an email and password. Upon successful registration, the endpoint returns a JWT token for immediate authentication.

- **Endpoint**: `POST /register`
- **Authentication**: Public (no authentication required)
- **Content-Type**: `application/json`

## 🏗️ Architecture Components

### 1. User Entity (`src/Entity/User.php`)

The main entity that implements Symfony's security interfaces:

```php
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',              // Custom URI template
            controller: RegisterController::class,  // Custom controller
            input: UserRegistrationInput::class,   // Input DTO
            output: UserRegistrationOutput::class, // Output DTO
            security: null,                        // Public access
            name: 'register',                      // Operation name
            description: 'Register a new user account',
            openapi: new Operation(/* ... */),    // OpenAPI documentation
            deserialize: true,                     // Enable input deserialization
            validate: true,                        // Enable validation
            write: false                          // Disable auto-persistence
        ),
    ]
)]
```

**Key Variables Explained:**
- `uriTemplate`: Defines the custom endpoint URL (`/register`)
- `controller`: Points to the custom controller handling the logic
- `input`: Specifies the DTO class for request data
- `output`: Specifies the DTO class for response data
- `security`: Set to `null` for public access (no authentication required)
- `name`: Internal operation name for routing
- `openapi`: Detailed API documentation configuration
- `deserialize`: Converts JSON input to DTO object
- `validate`: Enables automatic validation of input data
- `write`: Disabled to prevent automatic entity persistence

### 2. Input DTO (`src/Dto/UserRegistrationInput.php`)

Defines and validates the registration request structure:

```php
class UserRegistrationInput
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 6, minMessage: 'Password must be at least {{ limit }} characters long')]
    public string $password;
}
```

**Validation Rules:**
- `email`: Must be a valid email address and not blank
- `password`: Must be at least 6 characters long and not blank

### 3. Output DTO (`src/Dto/UserRegistrationOutput.php`)

Defines the registration response structure:

```php
class UserRegistrationOutput
{
    public string $token;  // JWT authentication token
    public array $user;    // User information (id, email)
}
```

### 4. Registration Controller (`src/Controller/RegisterController.php`)

Handles the registration logic:

**Key Dependencies:**
- `EntityManagerInterface`: Database operations
- `UserPasswordHasherInterface`: Password hashing
- `JWTTokenManagerInterface`: JWT token generation

**Process:**
1. Check if user already exists
2. Create new user entity
3. Hash the password
4. Persist to database
5. Generate JWT token
6. Return response

## 📚 OpenAPI Documentation Variables

The `openapi` configuration provides comprehensive API documentation:

```php
openapi: new Operation(
    tags: ['Authentication'],                    // Groups endpoint in Swagger UI
    summary: 'Register a new user',             // Short description
    description: 'Creates a new user account...', // Detailed description
    requestBody: new RequestBody(               // Request documentation
        description: 'User registration data',
        content: new \ArrayObject([
            'application/json' => new MediaType(
                example: [                      // Example request
                    'email' => 'john.doe@example.com',
                    'password' => 'SecurePassword123!'
                ]
            )
        ])
    ),
    responses: [                               // Response documentation
        '201' => new Response(/* success response */),
        '400' => new Response(/* validation error */),
        '409' => new Response(/* user exists error */)
    ]
)
```

**Documentation Variables:**
- `tags`: Organizes endpoints in Swagger UI sections
- `summary`: Brief endpoint description
- `description`: Detailed explanation
- `requestBody`: Documents expected input format and examples
- `responses`: Documents all possible response codes and formats

## 🔧 Required Dependencies

Install the following packages:

```bash
composer require lexik/jwt-authentication-bundle
composer require symfony/validator
composer require symfony/password-hasher
```

## ⚙️ Configuration

### 1. JWT Configuration (`config/packages/lexik_jwt_authentication.yaml`)

```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
```

### 2. Environment Variables (`.env`)

```env
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
```

### 3. Generate JWT Keys

```bash
php bin/console lexik:jwt:generate-keypair
```

## 📖 API Documentation Access

- **Swagger UI**: `http://localhost:8000/api/docs`
- **OpenAPI JSON**: `http://localhost:8000/api/docs.json`
- **OpenAPI YAML**: `http://localhost:8000/api/docs.yaml`

## 🧪 Testing the Endpoint

### Successful Registration

**Request:**
```bash
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@example.com",
    "password": "SecurePassword123!"
  }'
```

**Response (201):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "email": "john.doe@example.com"
  }
}
```

### Validation Error

**Request:**
```bash
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "invalid-email",
    "password": "123"
  }'
```

**Response (400):**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "email: This value is not a valid email address.",
  "violations": [
    {
      "propertyPath": "email",
      "message": "This value is not a valid email address.",
      "code": "bd79c0ab-ddba-46cc-a703-a7a4b08de310"
    }
  ]
}
```

### User Already Exists

**Response (409):**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "User with this email already exists"
}
```

## 🔍 Key Features

- ✅ **Input Validation**: Automatic validation using Symfony constraints
- ✅ **Password Hashing**: Secure password storage
- ✅ **JWT Token Generation**: Immediate authentication capability
- ✅ **Error Handling**: Comprehensive error responses
- ✅ **API Documentation**: Auto-generated OpenAPI/Swagger documentation
- ✅ **Type Safety**: Strong typing with DTOs
- ✅ **Security**: Public endpoint with proper validation

## 🚀 Next Steps

This registration endpoint can be extended with:
- Email verification
- User profile fields
- Role-based registration
- Rate limiting
- CAPTCHA integration
- Social login integration