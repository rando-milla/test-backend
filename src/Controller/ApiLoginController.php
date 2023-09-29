<?php

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

class ApiLoginController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the token of the current user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items()
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorised',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items()
        )
    )]
    #[OA\Parameter(
        name: 'password',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'email',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'login')]
    public function index(#[CurrentUser] ?User $user, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
                'content' => $request->getMethod()
            ], Response::HTTP_UNAUTHORIZED);
        }
        $token = bin2hex(random_bytes(32));; // somehow create an API token for $user

        $accessToken = new AccessToken();
        $accessToken->setToken($token);
        $accessToken->setUser($user);
        $entityManager->persist($accessToken);
        $entityManager->flush();

        return $this->json([
            'token' => $token
        ]);
    }
}
