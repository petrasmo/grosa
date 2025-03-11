<?php
namespace App\Repository;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class UzsakymaiRepository
{
    private Connection $db;
    private Security $security;

    public function __construct(Connection $db, Security $security)
    {
        $this->db = $db;
        $this->security = $security;
    }

    public function getGaminiai(): array
    {
        $query = "
            SELECT a.id AS id, a.name AS text
            FROM ord_product a           
            WHERE a.deleted <> 1          
            AND a.public = 1 
            ORDER BY a.name
        ";

        return $this->db->executeQuery($query)->fetchAllAssociative();
    }

    public function getAtributaiPagalGamini(int $gaminioId): array
    {
        $query = "
            SELECT gfk_id, gfa_id, gfk_atributas, gfk_tipas 
            FROM gaminio_frm_atributai_klas 
            LEFT JOIN gaminio_frm_atributai 
            ON gfk_id = gfa_gfk_id 
            AND gfa_id_mechanism = :gaminys_id
            AND gfa_deleted IS NULL
            ORDER BY gfk_rusiavimas
        ";

        return $this->db->executeQuery($query, ['gaminys_id' => $gaminioId])->fetchAllAssociative();
    }

    public function issaugotiAtributus(array $data): void
    {
        // Gauk vartotoją iš saugumo komponento
        $gaminysId = $data['gaminys_id'];
        $user = $this->security->getUser();

        /** @var \App\Entity\User|null $user */
        if (!$user) {
            throw new AuthenticationException("Vartotojas neprisijungęs.");
        }
        
        $userId = $user->getId(); // Dabar klaidos nebebus
        

       


        foreach ($data['atributai'] as $item) {
            $gfaId = $item['gfa_id'] !== "" ? (int) $item['gfa_id'] : null;
            $gfkId = (int) $item['gfk_id'];
            $checked = $item['checked'] ? $gfkId : null;

            $this->db->executeStatement("
                CALL gaminio_frm_atributai_upsert(:gfa_id, :P_gfa_id_mechanism, :gfk_id, :user_id)
            ", [
                'gfa_id' => $gfaId,
                'P_gfa_id_mechanism' => $gaminysId,
                'gfk_id' => $checked,
                'user_id' => $userId
            ]);
        }
    }

}
