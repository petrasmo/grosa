<?php

namespace App\Controller;

use App\Service\OracleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;



class UzsakymaiController extends AbstractController
{
    #[Route('/uzsakymai', name: 'uzsakymai')]
    public function index(Request $request): Response
    {
        // Gauti sėkmės pranešimą iš query parametru
        $successMessage = $request->query->get('success');

        return $this->render('uzsakymai/index.html.twig', [
            'controller_name' => 'UzsakymaiController',
            'success' => $successMessage, // Perduodame Twig šablonui
        ]);
    }

    #[Route('/uzsakymai/upload', name: 'uzsakymai_upload_post', methods: ['POST'])]
    public function handleFileUpload(Request $request, OracleService $oracleService): Response
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return $this->redirectToRoute('uzsakymai', ['error' => 'Nepasirinktas failas!']);
        }

        // Nuskaitome failą
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $oracleService->executeQuery("BEGIN delete gr_uzsakymai; commit; END;");
        $insertedCount = 0;

        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex === 1) continue; // Praleidžiame antraštės eilutę

            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getValue();
            }

            if (count($cells) >= 12) { // Užtikriname, kad turime visus laukus
                $sql = "INSERT INTO gr_uzsakymai (uzs_eil_nr, uzs_gamintojas, uzs_plokstes_spalva_kodas, uzs_storis_mm, 
                    uzs_ilgis_mm, uzs_plotis_mm, uzs_daliu_kiekis, uzs_krasto_kairysis, uzs_krasto_desinysis, 
                    uzs_krasto_virsutinis, uzs_krasto_apatinis, uzs_komentarai) 
                    VALUES (:uzs_eil_nr, :uzs_gamintojas, :uzs_plokstes_spalva_kodas, :uzs_storis_mm, 
                    :uzs_ilgis_mm, :uzs_plotis_mm, :uzs_daliu_kiekis, :uzs_krasto_kairysis, :uzs_krasto_desinysis, 
                    :uzs_krasto_virsutinis, :uzs_krasto_apatinis, :uzs_komentarai)";

                $oracleService->executeQuery($sql, [
                    'uzs_eil_nr' => (int) $cells[0],
                    'uzs_gamintojas' => $cells[1] ?? null,
                    'uzs_plokstes_spalva_kodas' => $cells[2] ?? null,
                    'uzs_storis_mm' => (float) $cells[3],
                    'uzs_ilgis_mm' => (float) $cells[4],
                    'uzs_plotis_mm' => (float) $cells[5],
                    'uzs_daliu_kiekis' => (int) $cells[6],
                    'uzs_krasto_kairysis' => isset($cells[7]) ? (float) $cells[7] : null,
                    'uzs_krasto_desinysis' => isset($cells[8]) ? (float) $cells[8] : null,
                    'uzs_krasto_virsutinis' => isset($cells[9]) ? (float) $cells[9] : null,
                    'uzs_krasto_apatinis' => isset($cells[10]) ? (float) $cells[10] : null,
                    'uzs_komentarai' => $cells[11] ?? null
                ]);

                $insertedCount++;
            }
        }
        $oracleService->executeQuery("BEGIN GROSA.map_uzsakymai; END;");

        return $this->redirectToRoute('uzsakymai', ['success' => "Įkelta $insertedCount įrašų"]);
    }

    #[Route('/uzsakymai/data', name: 'uzsakymai_data', methods: ['GET'])]
    public function getUzsakymaiData(OracleService $oracleService): JsonResponse
    {
        $sql = "SELECT DISTINCT 
                UZS_PLOKSTES_SPALVA_KODAS AS GAMINIO_KODAS, 
                UZS_SIST_KODAS AS GAMINIO_SIST_KODAS, 
                UZS_STORIS_MM AS GAMINIO_STORIS 
            FROM GR_UZSAKYMAI";

        $result = $oracleService->fetchData($sql);
        if (empty($result)) {
            return $this->json([
                [
                    "gaminio_kodas" => "",
                    "gaminio_sist_kodas" => "",
                    "gaminio_storis" => ""
                ]
            ]);
        }
        

        return $this->json($result);
    }

    #[Route('/uzsakymai/gaminio-sistemos', name: 'uzsakymai_gaminio_sistemos', methods: ['GET'])]
    public function getGaminioSistemos(Request $request,OracleService $oracleService): JsonResponse
    {
        $gaminioKodas = $request->query->get('gaminio_kodas');
        $gaminioStoris = $request->query->get('gaminio_storis');
        $search = $request->query->get('search', '');

        $sql = "
        SELECT stulp_c AS GAMINIO_SIST_KODAS 
        FROM GRASO
        WHERE TO_NUMBER(stulp_u) = :gaminio_storis
          AND upper(stulp_c) LIKE :search
        ORDER BY grosa.calculate_similarity_percentage(upper(stulp_c), upper(:gaminioKodas)) DESC
    ";

    // 4️⃣ Užpildome parametrus
    $params = [
        'gaminio_storis' => $gaminioStoris,
        'search' => '%' . strtoupper($search) . '%', // LIKE reikia "%...%"
        'gaminioKodas' => strtoupper($gaminioKodas) // Skaičiavimo funkcijai
    ];

    // 5️⃣ Vykdome SQL užklausą per OracleService
    $result = $oracleService->fetchData($sql, $params);
    $choices = array_map(function($row) {
        return [
            'id' => $row['GAMINIO_SIST_KODAS'],
            'text' => $row['GAMINIO_SIST_KODAS']
        ];
    }, $result);

    return new JsonResponse($choices);


    }

    #[Route('/uzsakymai/update-gaminio-kodas', name: 'update_gaminio_kodas', methods: ['POST'])]
    public function updateGaminioKodas(Request $request,OracleService $oracleService): JsonResponse
    {
        // Gauti reikšmes iš AJAX užklausos
        $selectedSistKodas = $request->request->get('gaminio_sist_kodas');
        $currentGaminioKodas = $request->request->get('gaminio_kodas');
        $selectedStoris = $request->request->get('gaminio_storis');


        // Tiesiog demonstravimui, kad reikšmės pasiekia kontrolerį
        $sql = "SELECT stulp_c GAMINIO_SIST_KODAS
        FROM GRASO t 
        WHERE TO_NUMBER(stulp_u) = :gaminio_storis
        ORDER BY grosa.calculate_similarity_percentage(UPPER(stulp_c), UPPER(:gaminio_kodas)) DESC";

// Bind parametrai
        $params = [
            'gaminio_storis' => $selectedStoris,
            'gaminio_kodas' => $currentGaminioKodas
        ];

// Vykdome užklausą su bind parametrais
        $result = $oracleService->fetchData($sql, $params);

        // Formatuojame į Select2 formatą
        $choices = array_map(function($row) {
            return [
                'id' => $row['GAMINIO_SIST_KODAS'],
                'text' => $row['GAMINIO_SIST_KODAS']
            ];
        }, $result);

        return new JsonResponse($choices);
    }

    #[Route('/uzsakymai/save', name: 'uzsakymai_save', methods: ['POST'])]
public function saveUzsakymai(Request $request, OracleService $oracleService): JsonResponse
{
    // 1️⃣ Gauti JSON duomenis iš užklausos
    $data = json_decode($request->getContent(), true);

    // 2️⃣ Tikriname, ar duomenys egzistuoja
    if (!isset($data['uzsakymai']) || !is_array($data['uzsakymai'])) {
        return new JsonResponse(['error' => 'Nėra tinkamų duomenų'], 400);
    }

    // 3️⃣ Iteruojame kiekvieną įrašą ir išsaugome į duomenų bazę
    foreach ($data['uzsakymai'] as $uzsakymas) {
        $sql = "update GR_UZSAKYMAI
            set uzs_sist_kodas=:gaminio_sist_kodas
            where uzs_plokstes_spalva_kodas=:gaminio_kodas and uzs_storis_mm=:gaminio_storis
        ";

        $params = [
            'gaminio_kodas' => $uzsakymas['gaminio_kodas'],
            'gaminio_sist_kodas' => $uzsakymas['gaminio_sist_kodas'],
            'gaminio_storis' => $uzsakymas['gaminio_storis']
        ];

        $oracleService->executeQuery($sql, $params);
    }

    return new JsonResponse(['success' => true, 'message' => 'Užsakymai sėkmingai išsaugoti']);
}


#[Route('/uzsakymai/download', name: 'uzsakymai_download', methods: ['GET'])]
public function downloadUzsakymaiCsv(OracleService $oracleService): StreamedResponse
{
    // 1️⃣ Gauname duomenis iš `gr_uzsakymai` lentelės
    $sql = "SELECT uzs_eil_nr, uzs_gamintojas, uzs_sist_kodas, uzs_plokstes_spalva_kodas, uzs_storis_mm, 
                   uzs_ilgis_mm, uzs_plotis_mm, uzs_daliu_kiekis, uzs_krasto_kairysis, 
                   uzs_krasto_desinysis, uzs_krasto_virsutinis, uzs_krasto_apatinis, uzs_komentarai
            FROM gr_uzsakymai
            ORDER BY uzs_eil_nr";
    $data = $oracleService->fetchData($sql);

    // 2️⃣ Sukuriame StreamedResponse atsakymą
    $response = new StreamedResponse(function () use ($data) {
        $output = fopen('php://output', 'w');

        // 3️⃣ Pridedame CSV antraštes (stulpelių pavadinimus)
        $headers = ['Eil. Nr', 'Manufacturer', 'System Code', 'Plate color, code', 'Thickness mm', 'Length, mm', 'Width, mm', 
                    'Amount of parts, pcs.', 'Left edge symbol', 'Right edge symbol', 'Top edge symbol', 'Bottom edge symbol', 'Comments'];
        fputcsv($output, $headers, ';'); // Naudojame ";" kaip skyriklį

        // 4️⃣ Pridedame duomenis į CSV
        foreach ($data as $row) {
            fputcsv($output, array_values($row), ';'); // Konvertuojame duomenis į paprastą masyvą
        }

        fclose($output);
    });

    // 5️⃣ Nustatome atsisiuntimo antraštes
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="uzsakymai.csv"');
    $response->headers->set('Cache-Control', 'max-age=0');

    return $response;
}

#[Route('/api/save-cuts', methods: ['POST'])]
    public function saveCuts(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
    
            if (!$data || !is_array($data)) {
                return new JsonResponse(['status' => 'error', 'message' => 'Netinkami duomenys'], 400);
            }
    
            // Testas: išveda gautus duomenis JSON formatu
            return new JsonResponse(['status' => 'success', 'received_data' => $data]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }



}
