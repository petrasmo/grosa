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
    public function index(Connection $connection): Response
    {
        $sql = "SELECT uzs_id, uzs_nr, uzs_create_date, uzs_busena, uzs_pristatymas, 0 AS uzs_suma 
                FROM uzsakymai 
                WHERE uzs_deleted = 0 
                ORDER BY uzs_create_date DESC";
        $uzsakymai = $connection->fetchAllAssociative($sql);

        return $this->render('uzsakymai/index.html.twig', [
            'uzsakymai' => $uzsakymai
        ]);
    }

    #[Route('/uzsakymai/redaguoti', name: 'uzsakymai_redaguoti', methods: ['GET'])]
    public function naujasUzsakymas(Request $request): Response
    {
        $uzs_id = $request->query->get('uzs_id'); 
        $gaminiai = $this->uzsakymaiRepository->getGaminiai();

              

        return $this->render('uzsakymai/redaguoti.html.twig', [
            'gaminiai' => $gaminiai, 
            'minWidth' => '',
            'maxWidth' => '',
            'uzs_id' => $uzs_id,
            'uze_id' => '',
            'spalvos'=>''
        ]);
    }

    #[Route('/gaminio-tipai/{gamId}', name: 'gaminio_tipai', methods: ['GET'])]
    public function getGaminioTipai(int $gamId, Connection $connection): JsonResponse
    {
        $sql = "SELECT a.id AS id, a.name AS text, a.min_width, a.max_warranty_width 
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
        $spalvos = $this->uzsakymaiRepository->getSpalvos($mechanismId);
        return $this->json($spalvos);
    }

    #[Route('/uzsakymai/medziagos-paieska', name: 'medziagos_paieska', methods: ['GET'])]
    public function medziagosPaieska(Request $request, Connection $connection): JsonResponse
    {
        $query = $request->query->get('q', '');
        $mechanismId = (int) $request->query->get('mechanism_id', 0); // GAUNAM MECHANISM ID

        if (strlen($query) < 2 || $mechanismId === 0) {
            return $this->json([]); // Jei per mažai simbolių arba mechanism_id nėra – grąžinam tuščią
        }

        $sql = "SELECT omm.id_material AS id, m2.wholesale_name AS text
                FROM ord_material_mechanism omm
                LEFT JOIN ord_roller_material m ON m.id = omm.id_material
                LEFT JOIN ord_material_mechanism mm ON mm.id_material = omm.id_material
                LEFT JOIN ord_roller_material m2 ON m2.id = mm.id_material
                WHERE mm.id_mechanism = :mechanism_id
                AND m2.wholesale_name LIKE :query
                AND omm.id_mechanism = :mechanism_id
                AND mm.deleted = 0
                AND m2.deleted = 0
                AND omm.wholesale = 1
                ORDER BY m2.wholesale_name
                LIMIT 50";

        $medziagos = $connection->fetchAllAssociative($sql, [
            'query' => "%$query%",
            'mechanism_id' => $mechanismId
        ]);

        return $this->json($medziagos);
    }

    #[Route('gaminio-plotis/{mechanismId}', name: 'gaminio_plotis', methods: ['GET'])]
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
    }
    
    #[Route('/gaminio-laukai/{mechanismId}', name: 'order_get_fields', methods: ['GET'])]
    public function getFields(Request $request, Connection $connection,int $mechanismId): JsonResponse
    {
       // $mechanismId = $request->query->get('mechanismId');


        // Užklausa gauti laukus pagal gaminio tipą
        $query = "SELECT gfk_id, gfa_id, gfk_atributas, gfk_tipas ,gfk_lauko_pavadinimas
                FROM gaminio_frm_atributai_klas
                JOIN gaminio_frm_atributai
                ON gfk_id = gfa_gfk_id
                AND gfa_id_mechanism = :mechanismId
                AND gfa_deleted IS NULL 
                ORDER BY gfk_rusiavimas";

        $fields = $connection->fetchAllAssociative($query, ['mechanismId' => $mechanismId]);
        $fieldNames = array_map(function($field) {
            return $field['gfk_lauko_pavadinimas'];
        }, $fields);
        $fieldNames = array_filter($fieldNames);


        $sql = "SELECT a.min_width min_width, a.max_warranty_width max_warranty_width
        FROM ord_roller_mechanism a
        WHERE id = :mechanismId
        AND a.deleted <> 1
        AND a.public = 1";

        $result = $connection->fetchAssociative($sql, ['mechanismId' => $mechanismId]);
        if (!$result) {
            throw $this->createNotFoundException('Mechanizmas nerastas'.$mechanismId);
        }

        $sql = "SELECT c.id, c.name 
                FROM ord_roller_mechanism_color omc
                JOIN ord_color c ON omc.id_color = c.id
                WHERE omc.id_mechanism = :mechanismId 
                AND c.deleted = 0
                ORDER BY c.name";

        $spalvos = $connection->fetchAllAssociative($sql, ['mechanismId' => $mechanismId]);
        return new JsonResponse([
            'fieldNames' => $fieldNames,
            'minWidth' => $result['min_width'],
            'maxWidth' => $result['max_warranty_width'],
            'spalvos' => $spalvos
        ]);
//dd($fields);
        /*return $this->json($spalvos);*/
      

        // Pasirenkame tinkamą Twig šabloną pagal mechanismId
        /*return $this->render('uzsakymai/fields.html.twig', [
            'mechanismId' => $mechanismId,
            'fieldNames' => $fieldNames,
            'minWidth' => $result['min_width'],
            'maxWidth' => $result['max_warranty_width'],
            'spalvos' => $spalvos

        ]);*/
    }

    #[Route('/uzsakymai/issaugoti', name: 'uzsakymai_issaugoti', methods: ['POST'])]
    public function issaugoti(Request $request, Connection $connection): Response
    {
        $duomenys = json_decode($request->getContent(), true);

        if ($duomenys === null) {
            return new JsonResponse(['success' => false, 'message' => 'Blogas JSON!'], 400);
        }

        $params = [
            'p_uzs_nr' => 'Pirmas',
            'p_uzs_aprasymas' => '',
            'p_uzs_busena' => 'N',
            'p_uzs_pristatymas' => 'N',
            'p_uzs_deleted' => 0,
            'p_uze_id_mechanism' => $duomenys['mechanism_id'] ?? null,
            'p_uze_vyriai' => $duomenys['vyriai'] ?? null,
            'p_uze_gaminio_spalva_id' => $duomenys['productColor'] ?? null,
            'p_uze_lameliu_spalva_id' => $duomenys['materialSelect'] ?? null,
            'p_uze_gaminio_plotis' => $duomenys['width'] ?? null,
            'p_uze_medziagos_plotis' => $duomenys['medzwidth'] ?? null,
            'p_uze_gaminio_aukstis' => $duomenys['heigth'] ?? null,
            'p_uze_medziagos_aukstis' => $duomenys['medzheigth'] ?? null,
            'p_uze_stabdymo_mechanizmas' => $duomenys['stabdymas'] ?? '',
            'p_uze_atitraukimo_kaladele' => $duomenys['atitraukimas'] ?? '',
            'p_uze_montavimas_i' => '',
            'p_uze_valo_itempimas' => '',
            'p_uze_apatinio_prof_fiksacija' => '',
            'p_uze_valdymas_puse' => $duomenys['valdymas'] ?? '',
            'p_uze_valdymas_tipas' => '',
            'p_uze_karnizo_med_apdaila' => '',
            'p_uze_karnizo_dangtelis' => '',
            'p_uze_skersinis' => '',
            'p_uze_juostelines_kopeteles' => '',
            'p_uze_nukreipiamieji_trosai' => '',
            'p_uze_lameliu_krastu_dazymas' => '',
            'p_uze_tipas' => '',
            'p_uze_kreipianciosios' => '',
            'p_uze_medziagos_pasunkinimas' => '',
            'p_uze_strypas' => '',
            'p_uze_rankenos_puse' => '',
            'p_uze_uzdarymo_tipas' => '',
            'p_uze_komentarai_gamybai' => $duomenys['comments'] ?? '',
            'p_uze_pristatymas' => '',
        ];

        try {
            // 1. SETINAM pradines reikšmes
            $connection->executeStatement('SET @p_uzs_id = :uzs_id, @p_uze_id = :uze_id', [
                'uzs_id' => !empty($duomenys['uzs_id']) ? (int)$duomenys['uzs_id'] : 0,
                'uze_id' => !empty($duomenys['uze_id']) ? (int)$duomenys['uze_id'] : 0,
            ]);

            // 2. Kvietimas
            $placeholders = implode(',', array_map(fn($key) => ':' . $key, array_keys($params)));
            $sql = "CALL insert_or_update_uzsakymas_and_eilutes(@p_uzs_id, @p_uze_id, $placeholders)";
            $connection->executeStatement($sql, $params);

            // 3. Tik po procedūros pasiimam ID
            $uzs_Id = $connection->fetchOne("SELECT @p_uzs_id");
            $uze_Id = $connection->fetchOne("SELECT @p_uze_id");

            return new JsonResponse([
                'success' => true,
                'message' => 'Išsaugota sėkmingai!',
                'uzs_Id' => $uzs_Id,
                'uze_Id' => $uze_Id,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Klaida: ' . $e->getMessage()]);
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

            return new JsonResponse(['success' => true, 'message' => 'Užsakymas pašalintas.']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Klaida: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/uzsakymai/uzsakymo-eilutes/{uzsId}', name: 'uzsakymo_eilutes', methods: ['GET'])]
    public function getUzsakymoEilutes(int $uzsId, Connection $connection): JsonResponse
    {
        $sql = "SELECT 
                    uze_id,
                    uzs_id AS uzs_nr, 
                    b.name AS gaminys, 
                    a.name AS gaminio_tipas,
                    uzs_busena, 
                    uzs_pristatymas,
                    uze_gaminio_plotis,
                    uze_gaminio_aukstis
                FROM 
                    uzsakymai, uzsakymo_eilutes, ord_roller_mechanism a, ord_product b
                WHERE 
                    uzs_id = uze_uzs_id 
                    AND uzs_id = :uzsId
                    AND uze_id_mechanism = a.id 
                    AND a.id_product = b.id";

        $eilutes = $connection->fetchAllAssociative($sql, ['uzsId' => $uzsId]);

        return $this->json($eilutes);
    }

    #[Route('/uzsakymai/uzsakymo-eilutes/redaguoti/{uzeId}', name: 'uzsakymo_eilute_redaguoti', methods: ['GET'])]
    public function redaguotiEilute(int $uzeId, Connection $connection): JsonResponse
    {
        $sql = "SELECT 
                    uze_id,
                    uzs_id,
                    uzs_id AS uzs_nr,
                    a.id_product AS gaminys_id,
                    a.id AS mechanism_id,
                    b.name AS gaminys,
                    a.name AS gaminio_tipas,
                    uzs_busena, 
                    uzs_pristatymas,
                    uze_gaminio_plotis,
                    uze_gaminio_aukstis,
                    uze_atitraukimo_kaladele,
                    uze_vyriai,
                    uze_gaminio_spalva_id,
                    uze_lameliu_spalva_id,
                    'ALZ 16 2 - 01' as uze_lameles_spalva
                FROM 
                    uzsakymai, uzsakymo_eilutes, ord_roller_mechanism a, ord_product b
                WHERE 
                    uze_id = :uzeId
                    AND uzs_id = uze_uzs_id 
                    AND uze_id_mechanism = a.id 
                    AND a.id_product = b.id";
                    //atitraukimas

        $eilute = $connection->fetchAssociative($sql, ['uzeId' => $uzeId]);

        if (!$eilute) {
            return $this->json(['success' => false, 'message' => 'Eilutė nerasta.'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $eilute
        ]);
    }

}
