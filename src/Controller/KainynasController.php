<?php
namespace App\Controller;

use App\Repository\KainynasRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class KainynasController extends AbstractController
{
    private KainynasRepository $kainynasRepository;

    public function __construct(KainynasRepository $kainynasRepository)
    {
        $this->kainynasRepository = $kainynasRepository;
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
        /*if ($material === '') {
            // Jei material tuščias, grąžink visus 1000 įrašų
            $rezultatai = $this->kainynasRepository->gautiVisusIrasaus();
        } else {
            // Jei įvesta kažkas, ieškom pagal įvestą reikšmę
            $rezultatai = $this->kainynasRepository->ieskotiKainu($material);
        }*/

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
}