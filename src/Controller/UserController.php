<?php

namespace App\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Controller\BaseController;

class UserController extends BaseController
{
    #[Route('/api/me', name: 'app_apime')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function apiMe()
    {
        return $this->json($this->getUser(), 200 , [] , [
            'groups' => ['user:read']
        ]);
    }
}