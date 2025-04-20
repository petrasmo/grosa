<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(): Response
    {        
        $uzsakymai = $this->uzsakymaiRepository->getuzsakymai();
      
        return $this->render('uzsakymai/index.html.twig', [
            'uzsakymai' => $uzsakymai
        ]);
    }

    #[Route('/uzsakymai/redaguoti', name: 'uzsakymai_redaguoti', methods: ['GET'])]
    public function naujasUzsakymas(Request $request): Response
    {
        $uzsakymas=[];
        $uzs_id = $request->query->get('uzs_id'); 
        $gaminiai = $this->uzsakymaiRepository->getGaminiai(); 
        if (!empty($uzs_id))             
            $uzsakymas = $this->uzsakymaiRepository->getuzsakymai($uzs_id);

        return $this->render('uzsakymai/redaguoti.html.twig', [
            'gaminiai' => $gaminiai, 
            'minWidth' => '',
            'maxWidth' => '',
            'uzs_id' => $uzs_id,
            'uze_id' => '',
            'uzs_nr' => $uzsakymas[0]['uzs_nr'] ?? '',
            'uzs_pristatymas' => $uzsakymas[0]['uzs_pristatymas'] ?? '',           
            'spalvos'=>''
        ]);
    }

    #[Route('/gaminio-tipai/{gamId}', name: 'gaminio_tipai', methods: ['GET'])]
    public function getGaminioTipai(int $gamId): JsonResponse
    {       
        $gaminioTipai = $this->uzsakymaiRepository->getgaminiotipai($gamId);  

        return $this->json($gaminioTipai);
    }

    #[Route('/gaminio-spalvos/{mechanismId}', name: 'gaminio_spalvos', methods: ['GET'])]
    public function getGaminioSpalvos(int $mechanismId, Connection $connection): JsonResponse
    {
        $spalvos = $this->uzsakymaiRepository->getSpalvos($mechanismId);
        return $this->json($spalvos);
    }

    #[Route('/uzsakymai/medziagos-paieska', name: 'medziagos_paieska', methods: ['GET'])]
    public function medziagosPaieska(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $mechanismId = (int) $request->query->get('mechanism_id', 0);
        $rezultatai = $this->uzsakymaiRepository->ieskotiMedziagu($query, $mechanismId);

        return $this->json($rezultatai);
    }

    /*#[Route('gaminio-plotis/{mechanismId}', name: 'gaminio_plotis', methods: ['GET'])]
    public function getGaminioPlotis(int $mechanismId, Connection $connection): JsonResponse
    {
        $sql = "SELECT a.min_width, a.max_warranty_width
                FROM ord_roller_mechanism a
                WHERE id = :mechanismId
                AND a.deleted <> 1
                AND a.public = 1";

        $result = $connection->fetchAssociative($sql, ['mechanismId' => $mechanismId]);

        // Patikriname, ar buvo rasta reikšmių
        if (!$result) {
            return $this->json(['error' => 'Gaminio tipas nerastas'], 404);
        }

        return $this->json([
            'minWidth' => $result['min_width'],
            'maxWidth' => $result['max_warranty_width']
        ]);
    }*/
    
    #[Route('/gaminio-laukai/{mechanismId}', name: 'order_get_fields', methods: ['GET'])]
    public function getFields(int $mechanismId): JsonResponse
    {
        try {
            $duomenys = $this->uzsakymaiRepository->getMechanizmoLaukai($mechanismId);
            return $this->json($duomenys);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/uzsakymai/issaugoti', name: 'uzsakymai_issaugoti', methods: ['POST'])]
    public function issaugoti(Request $request): JsonResponse
    {
        $duomenys = json_decode($request->getContent(), true);

        if ($duomenys === null) {
            return new JsonResponse(['success' => false, 'message' => 'Blogas JSON!'], 400);
        }

        try {
            $rez = $this->uzsakymaiRepository->issaugotiUzsakymaIrEilute($duomenys);

            return new JsonResponse([
                'success' => true,
                'message' => 'Ušsakymas sėkmingai išsaugotas',
                'uzs_Id' => $rez['uzs_Id'],
                'uze_Id' => $rez['uze_Id'],
                'uzs_nr' => $rez['uzs_nr']         
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Klaida: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/uzsakymai/salinti/{id}', name: 'uzsakymai_salinti', methods: ['POST'])]
    public function salinti(int $id, Connection $connection, Request $request): JsonResponse
    {
        // Paimk naudotojo vardą arba ID (jei reikia)
        $user = 1;

        try {
            // Iškviečiame procedūrą
            $connection->executeStatement('CALL delete_uzsakymas_ir_eilutes(:uzs_id, :user)', [
                'uzs_id' => $id,
                'user' => $user,
            ]);

            return new JsonResponse(['success' => true, 'message' => 'Užsakymas sėkmingai pašalintas']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Klaida: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/uzsakymai/uzsakymo-eilutes/salinti', name: 'uzsakymo_eilutes_salinti', methods: ['POST'])]
    public function salintileitute(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $uzeId = $data['uze_id'] ?? null;
        $uzsId = $data['uzs_id'] ?? null;

        if (!$uzeId || !$uzsId) {
            return new JsonResponse(['success' => false, 'message' => 'Trūksta ID.'], 400);
        }

        try {
            $this->uzsakymaiRepository->salintiEilute((int)$uzeId, (int)$uzsId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Eilutė pašalinta.',
                'uzs_Id' => $uzsId
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Klaida šalinant: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/uzsakymai/uzsakymo-eilutes/{uzsId}', name: 'uzsakymo_eilutes', methods: ['GET'])]
    public function getUzsakymoEilutes(int $uzsId): JsonResponse
    {
        $eilutes = $this->uzsakymaiRepository->getUzsakymoEilutes($uzsId);

        return $this->json($eilutes);
    }

    #[Route('/uzsakymai/gaminio-kaina', name: 'get_price', methods: ['GET'])]
    public function getPrice(Request $request, UzsakymaiRepository $repository): JsonResponse
    {
        // 1. Gaunam GET parametrus
        $idProduct = $request->query->get('id_product');
        $idMechanism = $request->query->get('id_mechanism');
        $idColor = $request->query->get('id_color');
        $heigth = (int) $request->query->get('heigth');
        $width = (int) $request->query->get('width');

        // 2. Kvietimas į MariaDB procedūrą per repozitoriją
        $rezultatas = $repository->getPrice([
            'id_product' => $idProduct,
            'id_mechanism' => $idMechanism,
            'id_color' => $idColor,
            'heigth' => $heigth,
            'width' => $width,
        ]);

        // 3. Jei yra klaida – grąžinam su 400 statusu
        if (!empty($rezultatas['klaida'])) {
            return $this->json([
                'klaida' => $rezultatas['klaida'],
            ], 400);
        }

        // 4. Grąžinam kainas
        return $this->json([
            'kaina_su_pvm' => $rezultatas['kaina_su_pvm'],
            'kaina_be_pvm' => $rezultatas['kaina_be_pvm'],
        ]);
    }

    
    

}
