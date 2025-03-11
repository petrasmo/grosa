<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use App\Repository\UzsakymaiRepository; // <-- Šito trūko!

class UzsakymaiController extends AbstractController
{
    private UzsakymaiRepository $uzsakymaiRepository; // <-- Teisingas pavadinimas su mažąja raide

    public function __construct(UzsakymaiRepository $uzsakymaiRepository)
    {
        $this->uzsakymaiRepository = $uzsakymaiRepository; // <-- Teisingas pavadinimas
    }

    #[Route('/uzsakymai', name: 'uzsakymai')]
    public function index(Connection $connection): Response
    {
        $sql = "SELECT uzs_id, uzs_nr, uzs_create_date, uzs_busena, uzs_pristatymas, 0 AS uzs_suma 
                FROM uzsakymai 
                WHERE uzs_deleted IS NULL 
                ORDER BY uzs_create_date DESC";
        $uzsakymai = $connection->fetchAllAssociative($sql);

        return $this->render('uzsakymai/index.html.twig', [
            'uzsakymai' => $uzsakymai
        ]);
    }

    #[Route('/uzsakymai/redaguoti', name: 'uzsakymai_redaguoti')]
    public function naujasUzsakymas(): Response
    {
        $gaminiai = $this->uzsakymaiRepository->getGaminiai();

        return $this->render('uzsakymai/redaguoti.html.twig', [
            'gaminiai' => $gaminiai, 
        ]);
    }

    #[Route('/gaminio-tipai/{gamId}', name: 'gaminio_tipai', methods: ['GET'])]
    public function getGaminioTipai(int $gamId, Connection $connection): JsonResponse
    {
      //aaaaafdg df gdfg dfg 
        $sql = "SELECT a.id AS id, a.name AS text 
                FROM ord_roller_mechanism a
                WHERE a.id_product = :gamId 
                AND a.deleted <> 1 
                AND a.public = 1
                ORDER BY a.name";  

        $gaminioTipai = $connection->fetchAllAssociative($sql, ['gamId' => $gamId]);

        return $this->json($gaminioTipai); 
    }

    #[Route('/gaminio-spalvos/{mechanismId}', name: 'gaminio_spalvos', methods: ['GET'])]
    public function getGaminioSpalvos(int $mechanismId, Connection $connection): JsonResponse
    {
        $sql = "SELECT c.id, c.name 
                FROM ord_roller_mechanism_color omc
                JOIN ord_color c ON omc.id_color = c.id
                WHERE omc.id_mechanism = :mechanismId 
                AND c.deleted = 0
                ORDER BY c.name";

        $spalvos = $connection->fetchAllAssociative($sql, ['mechanismId' => $mechanismId]);

        return $this->json($spalvos);
    }
}
