<?php

namespace App\Dto\registrationDto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * User registration input data
 */
class UserRegistrationInput
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
     * User's password (minimum 6 characters)
     * 
     * @example "SecurePassword123!"
     */
    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 6,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    public string $password;
}