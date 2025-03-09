<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class OrderController extends AbstractController
{
    #[Route('/order', name: 'order')]
    public function index(): Response
    {      
       

        return $this->render('order/index.html.twig', [
            'rows' => '',
            'filters' => '',
        ]);
    }

    #[Route('/order/update', name: 'update')]
    public function update(): Response
    {
        // Čia galima pridėti logiką naujo užsakymo kūrimui
        return $this->render('order/update.html.twig', [
            'rows' => '',
            'filters' => '',
        ]);
    }
    

}