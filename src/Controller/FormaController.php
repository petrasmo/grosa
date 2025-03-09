<?php
namespace App\Controller;

use Doctrine\DBAL\Connection;
use App\Repository\FormosRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;



class FormaController extends AbstractController
{
    private FormosRepository $formosRepository;

    public function __construct(FormosRepository $formosRepository)
    {
        $this->formosRepository = $formosRepository;
    }

    #[Route('/formos', name: 'formos', methods: ['GET'])]
    public function index(Connection $db): Response
    {
        $gaminiai = $this->formosRepository->getGaminiai();

        return $this->render('formos/index.html.twig', [
            'gaminiai' => $gaminiai, // Paduodame duomenis į Twig
        ]);
    }

    #[Route('/formos/atributai', name: 'formos_atributai', methods: ['GET'])]
    public function atributai(Request $request, Connection $db): JsonResponse
    {
        $gaminioId = $request->query->getInt('gaminys_id');

        if (!$gaminioId) {
            return new JsonResponse(['error' => 'Trūksta gaminio ID'], 400);
        }

        $result = $this->formosRepository->getAtributaiPagalGamini($gaminioId);

        return new JsonResponse($result);
    }

    #[Route('/formos/atributai/issaugoti', name: 'formos_atributai_issaugoti', methods: ['POST'])]
    public function issaugoti(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['gaminys_id']) || !isset($data['atributai'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Trūksta duomenų formoje.'
                ], 400);
            }

            
            $this->formosRepository->issaugotiAtributus($data);


            return new JsonResponse([
                'success' => true,
                'message' => 'Atributai sėkmingai išsaugoti!'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Klaida išsaugant: ' . $e->getMessage()
            ], 500);
        }
    }

}
