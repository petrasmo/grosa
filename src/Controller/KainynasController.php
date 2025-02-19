<?php

namespace App\Controller;
use App\Service\OracleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class KainynasController extends AbstractController
{
    #[Route('/kainynas', name: 'kainynas')]
    public function index(): Response
    {
        return $this->render('kainynas/index.html.twig', [
            'controller_name' => 'KainynasController',
        ]);
    }

    #[Route('/kainynas/test', name: 'kainynas_test')]
    public function testConnection(OracleService $oracleService): Response
    {
        try {
            $query = "SELECT SYSDATE AS current_date FROM dual"; // Paimame dabartinę Oracle DB datą
            $result = $oracleService->fetchData($query);

            return new Response('<pre>' . print_r($result, true) . '</pre>');
        } catch (\Exception $e) {
            return new Response('Klaida: ' . $e->getMessage());
        }
    }
}