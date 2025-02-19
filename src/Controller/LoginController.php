<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Bundle\SecurityBundle\Security;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, TokenStorageInterface $tokenStorage, Security $security): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('kainynas'); // Jei jau prisijungęs, peradresuojame
        }

        // Gauti klaidos pranešimą, jei autentifikacija nepavyko
        $error = $authenticationUtils->getLastAuthenticationError();

        // Debug'inimui - patikrinti autentifikavimo informaciją
        $token = $tokenStorage->getToken();
        $user = $token?->getUser();
        dump($token);
        dump($user);

        return $this->render('login/login.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony automatiškai tvarko šią funkciją
    }
}