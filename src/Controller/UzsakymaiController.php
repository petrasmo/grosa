<?php

namespace App\Controller;

use App\Service\OracleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        return $this->redirectToRoute('uzsakymai', ['success' => "Įkelta $insertedCount įrašų"]);
    }
}
