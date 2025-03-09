<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\OracleService;

class KlasifikatoriusController extends AbstractController
{
    #[Route('/klasifikatoriai', name: 'sist_klasifikatorius')]
    public function index(OracleService $oracleService): Response
    {      
        $query = "SELECT stulp_c AS kodas, stulp_d AS spalva_pavadinimas, stulp_s AS ilgis, stulp_t AS plotis, stulp_u AS storis FROM graso";
        $rows = array_map(fn($row) => array_change_key_case($row, CASE_LOWER), $oracleService->fetchData($query));

        // Unikalios reikšmės filtrams
        $filterValues = [
            'ilgis' => array_unique(array_column($rows, 'ilgis')),
            'plotis' => array_unique(array_column($rows, 'plotis')),
            'storis' => array_unique(array_column($rows, 'storis')),
        ];

        sort($filterValues['ilgis'], SORT_NUMERIC);
        sort($filterValues['plotis'], SORT_NUMERIC);
        sort($filterValues['storis'], SORT_NUMERIC);

        return $this->render('klasifikatoriai/index.html.twig', [
            'rows' => $rows,
            'filters' => $filterValues,
        ]);
    }

}