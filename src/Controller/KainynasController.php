<?php
namespace App\Controller;

use App\Repository\KainynasRepository;
use App\Repository\UzsakymaiRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class KainynasController extends AbstractController
{
    private KainynasRepository $kainynasRepository;
    private UzsakymaiRepository $uzsakymaiRepository;

    public function __construct(KainynasRepository $kainynasRepository, UzsakymaiRepository $uzsakymaiRepository)
    {
        $this->kainynasRepository = $kainynasRepository;
        $this->uzsakymaiRepository = $uzsakymaiRepository;
    }

    #[Route('/kainynas', name: 'kainynas', methods: ['GET'])]
    public function index(Connection $db): Response
    {
        $duomenys = $this->kainynasRepository->gautiVisusIrasaus();

        return $this->render('kainynas/index.html.twig', [
            'kainynas' => $duomenys,
        ]);
    }

    #[Route('/kainynas/ieskoti', name: 'kainynas_ieskoti', methods: ['GET'])]
    public function ieskoti(Request $request): JsonResponse
    {        
        $filters = [
            'gaminys' => $request->query->get('gaminys', ''),
            'tipas' => $request->query->get('tipas', ''),
            'spalva' => $request->query->get('spalva', ''),
            'medziaga' => $request->query->get('medziaga', ''),
        ];

        $rezultatai = $this->kainynasRepository->ieskotiKainu($filters);
        
        return new JsonResponse($rezultatai);
    }

    #[Route('/kainynas/issaugoti', name: 'kainynas_issaugoti_kainas', methods: ['POST'])]
    public function issaugotiKainas(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['kainos']) || !is_array($data['kainos'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Blogas duomenų formatas.'
                ], 400);
            }

            $this->kainynasRepository->issaugotiKeliasKainas($data['kainos']);

            return new JsonResponse([
                'success' => true,
                'message' => 'Kainos sėkmingai išsaugotos.'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Klaida: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/kainynas/kainu-taisykles', name: 'kainu_taisykles', methods: ['GET'])]
    public function taisykles(): Response
    {
        $gaminiai = $this->uzsakymaiRepository->getGaminiai();
        $spalvos = [];
        $gaminio_tipai = [];
        $medziagos = [];

        return $this->render('kainynas/taisykles.html.twig', [
            'gaminiai' => $gaminiai,
            'spalvos' => $spalvos,
            'gaminio_tipai' => $gaminio_tipai,
            'medziagos' => $medziagos,
        ]);
    }

    #[Route('/kainynas/kainu-taisykles/ieskoti', name: 'kainu_taisykles_ieskoti', methods: ['GET'])]
    public function ieskotiKainuTaisykliu(Request $request): JsonResponse
    {        
        $filters = [
            'gaminys' => $request->query->get('gaminys', ''),
            'tipas' => $request->query->get('tipas', ''),
            'spalva' => $request->query->get('spalva', ''),
            'medziaga' => $request->query->get('medziaga', ''),
        ];

        $rezultatai = $this->kainynasRepository->ieskotiKainuTaisykliu($filters);
        
        return new JsonResponse($rezultatai);
    }
}
