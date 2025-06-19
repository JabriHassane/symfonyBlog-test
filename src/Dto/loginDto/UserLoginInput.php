<?php

namespace App\Dto\loginDto;

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