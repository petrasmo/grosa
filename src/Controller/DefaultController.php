<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/uBikeAdmin', name: 'app_default')]
    public function index(): Response
    {
        return $this->render('uBikeAdmin/admin.html.twig', [
            'controller_name' => 'DefaultController',
        ]);  
    }
}
