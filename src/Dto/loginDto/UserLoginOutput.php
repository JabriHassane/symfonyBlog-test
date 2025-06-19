<?php

namespace App\Dto\loginDto;

use Symfony\Component\Validator\Constraints\Date;

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

    /**
     * Token expiration timestamp
     * 
     * @example 1718911200
     */
    public string $date;

    public function __construct(string $token, int $userId, string $email, array $roles, int $expiresAt, string $date)
    {
        $this->token = $token;
        $this->user = [
            'id' => $userId,
            'email' => $email,
            'roles' => $roles,
        ];
        $this->expiresAt = $expiresAt;
        $this->date = $date;
    }
}