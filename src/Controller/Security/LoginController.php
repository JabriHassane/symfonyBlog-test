<?

namespace App\Controller\Security;

use App\Dto\loginDto\UserLoginInput;
use App\Dto\loginDto\UserLoginOutput;
use App\Entity\Users;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;

class LoginController extends AbstractController
{
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SerializerInterface $serializer,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function __invoke(Request $request, UserLoginInput $input): JsonResponse
    {

        // Parse request content
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON payload', 400);
        }

        $input->email = $data['email'] ?? null;
        $input->password = $data['password'] ?? null;

        // Find user by email
        $user = $this->entityManager->getRepository(Users::class)
            ->findOneBy(['email' => $input->email]);

        if (!$user) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $input->password)) {
            throw new BadRequestHttpException('Invalid credentials');
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);
        
        // Calculate expiration time (default: 1 hour)
        $expiresAt = time() + 3600;
        $date = date('Y-m-d H:i:s', $expiresAt);

        // Create output DTO
        $output = new UserLoginOutput(
            $token,
            $user->getId(),
            $user->getEmail(),
            $user->getRoles(),
            $expiresAt,
            $date
        );

        // Serialize output DTO to JSON
        $json = $this->serializer->serialize($output, 'json');

        return new JsonResponse($json, 200, [], true);
    }
    
}