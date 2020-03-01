<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TodoController extends AbstractController
{
    /**
     * @var User
     */
    private $currentUser;

    /**
     * TodoController constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->currentUser = $tokenStorage->getToken()->getUser();
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function getOne(int $id): JsonResponse
    {
        // todo: Implement later . Get Todo for current user by id
        return $this->json([
            'method' => 'getOne',
            'id' => $id,
            'userId' => $this->currentUser->getId(),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function getAll(): JsonResponse
    {
        // todo: Implement later
        return $this->json([
            'method' => 'getAll',
            'userId' => $this->currentUser->getId(),
        ]);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(int $id, Request $request): JsonResponse
    {
        // todo: Implement later
        return $this->json([
            'method' => 'update',
            'id' => $id,
            'userId' => $this->currentUser->getId(),
            'data' => $request->request->all(),
        ]);
    }

    /**
     * @param int $id
     *
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        // todo: Implement later
        return $this->json([
            'method' => 'delete',
            'id' => $id,
            'userId' => $this->currentUser->getId(),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        // todo: Implement later
        return $this->json([
            'method' => 'create',
            'userId' => $this->currentUser->getId(),
        ]);
    }
}