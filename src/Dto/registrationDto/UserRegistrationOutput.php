<?php

namespace App\Dto\registrationDto;


/**
 * User registration response
 */
class UserRegistrationOutput
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
     * @example {"id": 1, "email": "john.doe@example.com"}
     */
    public array $user;

    public function __construct(string $token, int $userId, string $email)
    {
        $this->token = $token;
        $this->user = [
            'id' => $userId,
            'email' => $email,
        ];
    }
}