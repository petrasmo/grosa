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
            SELECT a.id AS value, a.name AS label
            FROM ord_product a           
            WHERE a.deleted <> 1          
            AND a.public = 1 
            ORDER BY a.name
        ";

        return $this->db->executeQuery($query)->fetchAllAssociative();
    }

    public function getSpalvos(int $Id): array
    {
        $query = "SELECT c.id, c.name 
    FROM ord_roller_mechanism_color omc
    JOIN ord_color c ON omc.id_color = c.id
    WHERE omc.id_mechanism = :mechanismId 
    AND c.deleted = 0
    ORDER BY c.name";

      
        return $this->db->executeQuery($query, ['mechanismId' => $Id])->fetchAllAssociative();
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

    public function getuzsakymai(?int $uzsId = null): array
    {
        $query = "SELECT uzs_id, uzs_nr, uzs_create_date, uzs_busena, uzs_pristatymas, 0 AS uzs_suma 
                FROM uzsakymai 
                WHERE uzs_deleted = 0";

        $params = [];

        if ($uzsId !== null) {
            $query .= " AND uzs_id = :uzs_id";
            $params['uzs_id'] = $uzsId;
        }

        $query .= " ORDER BY uzs_create_date DESC";

        return $this->db->executeQuery($query, $params)->fetchAllAssociative();
    }

    public function getgaminiotipai(int $gamId): array
    {
        
        $query = "SELECT a.id AS id, a.name AS text, a.min_width, a.max_warranty_width 
                FROM ord_roller_mechanism a
                WHERE a.id_product = :gamId 
                AND a.deleted <> 1 
                AND a.public = 1
                ORDER BY a.name";         
      
        return $this->db->executeQuery($query,['gamId' => $gamId])->fetchAllAssociative();
    }

    public function ieskotiMedziagu(string $query, int $mechanismId): array
    {
        if ($mechanismId === 0) {
            return []; // Reikia mechanizmo
        }

        $sql = "SELECT omm.id_material AS id, m2.wholesale_name AS text
                FROM ord_material_mechanism omm
                LEFT JOIN ord_roller_material m ON m.id = omm.id_material
                LEFT JOIN ord_material_mechanism mm ON mm.id_material = omm.id_material
                LEFT JOIN ord_roller_material m2 ON m2.id = mm.id_material
                WHERE mm.id_mechanism = :mechanism_id
                AND omm.id_mechanism = :mechanism_id
                AND mm.deleted = 0
                AND m2.deleted = 0
                AND omm.wholesale = 1";

        $params = ['mechanism_id' => $mechanismId];

        // Jeigu įvesta bent 1 simbolis – taikom LIKE
        if (strlen(trim($query)) >= 1) {
            $sql .= " AND m2.wholesale_name LIKE :query";
            $params['query'] = '%' . $query . '%';
        }

        $sql .= " ORDER BY m2.wholesale_name LIMIT 50";

        return $this->db->fetchAllAssociative($sql, $params);
    }

    public function getMechanizmoLaukai(int $mechanismId): array
    {
        // 1. Gauti dinaminį laukų sąrašą
        $query = "SELECT gfk_id, gfa_id, gfk_atributas, gfk_tipas, gfk_lauko_pavadinimas
                FROM gaminio_frm_atributai_klas
                JOIN gaminio_frm_atributai ON gfk_id = gfa_gfk_id
                WHERE gfa_id_mechanism = :mechanismId
                AND gfa_deleted IS NULL
                ORDER BY gfk_rusiavimas";

        $fields = $this->db->fetchAllAssociative($query, ['mechanismId' => $mechanismId]);

        $fieldNames = array_filter(array_map(fn($f) => $f['gfk_lauko_pavadinimas'], $fields));

        // 2. Gauti min/max width
        $widths = $this->db->fetchAssociative("
            SELECT a.min_width, a.max_warranty_width
            FROM ord_roller_mechanism a
            WHERE id = :mechanismId
            AND a.deleted <> 1
            AND a.public = 1
        ", ['mechanismId' => $mechanismId]);

        if (!$widths) {
            throw new \RuntimeException("Mechanizmas nerastas: $mechanismId");
        }

        // 3. Gauti spalvas
        $colors = $this->db->fetchAllAssociative("
            SELECT c.id, c.name
            FROM ord_roller_mechanism_color omc
            JOIN ord_color c ON omc.id_color = c.id
            WHERE omc.id_mechanism = :mechanismId
            AND c.deleted = 0
            ORDER BY c.name
        ", ['mechanismId' => $mechanismId]);

        return [
            'fieldNames' => $fieldNames,
            'minWidth' => $widths['min_width'],
            'maxWidth' => $widths['max_warranty_width'],
            'spalvos' => $colors
        ];
    }

    public function issaugotiUzsakymaIrEilute(array $duomenys): array
    {
        $params = [            
            'p_uzs_aprasymas' => '',
            'p_uzs_busena' => 'N',
            'p_uzs_pristatymas' => $duomenys['uzs_pristatymas'] ?? null,
            'p_uzs_deleted' => 0,
            'p_uze_id_mechanism' => $duomenys['mechanism_id'] ?? null,
            'p_uze_vyriai' => $duomenys['vyriai'] ?? null,
            'p_uze_gaminio_spalva_id' => $duomenys['productColor'] !== '' ? $duomenys['productColor'] : null,
            'p_uze_lameliu_spalva_id' => $duomenys['materialId'] !== '' ? $duomenys['materialId'] : null,
            'p_uze_gaminio_plotis' => $duomenys['width'] !== '' ? $duomenys['width'] : null,
            'p_uze_medziagos_plotis' => $duomenys['medzwidth'] !== '' ? $duomenys['medzwidth'] : null,
            'p_uze_gam_plocio_sutikimas' => $duomenys['width_agreement'] ?? null,
            'p_uze_gaminio_aukstis' => $duomenys['heigth'] !== '' ? $duomenys['heigth'] : null,
            'p_uze_medziagos_aukstis' => $duomenys['medzheigth'] !== '' ? $duomenys['medzheigth'] : null,
            'p_uze_stabdymo_mechanizmas' => $duomenys['stabdymas'] ?? '',
            'p_uze_atitraukimo_kaladele' => $duomenys['atitraukimas'] ?? '',
            'p_uze_montavimas_i' => $duomenys['montavimasi'] ?? null,
            'p_uze_virsnisos_cm' => $duomenys['virsnisoscm'] !== '' ? (int)$duomenys['virsnisoscm'] : null,
            'p_uze_valo_itempimas' => $duomenys['valoitempimas'] ?? '',
            'p_uze_apatinio_prof_fiksacija' => $duomenys['approfiliofiks'] ?? '',
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

        // 1. SET pradines reikšmes
        $this->db->executeStatement('SET @p_uzs_id = :uzs_id, @p_uze_id = :uze_id, @p_uzs_nr = :uzs_nr', [
            'uzs_id' => !empty($duomenys['uzs_id']) ? (int)$duomenys['uzs_id'] : 0,
            'uze_id' => !empty($duomenys['uze_id']) ? (int)$duomenys['uze_id'] : 0,
            'uzs_nr' => $duomenys['uzs_nr'] ?? '',
        ]);

        // 2. CALL
        $placeholders = implode(',', array_map(fn($key) => ':' . $key, array_keys($params)));
        $sql = "CALL insert_or_update_uzsakymas_and_eilutes(@p_uzs_id, @p_uze_id, @p_uzs_nr, $placeholders)";
        $this->db->executeStatement($sql, $params);

        // 3. Gauti ID
        $uzs_Id = $this->db->fetchOne("SELECT @p_uzs_id");
        $uze_Id = $this->db->fetchOne("SELECT @p_uze_id");
        $uzs_Nr = $this->db->fetchOne("SELECT @p_uzs_nr");

        return [
            'uzs_Id' => $uzs_Id,
            'uze_Id' => $uze_Id,
            'uzs_nr' => $uzs_Nr
        ];
    }



    public function salintiEilute(int $uzeId, int $uzsId): bool
    {
        $sql = 'UPDATE uzsakymo_eilutes 
                SET uze_deleted = 1 
                WHERE uze_id = :uze_id AND uze_uzs_id = :uzs_id';

        $this->db->executeStatement($sql, [
            'uze_id' => $uzeId,
            'uzs_id' => $uzsId,
        ]);

        return true;
    }

    public function getUzsakymoEilutes(int $uzsId): array
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
                    AND a.id_product = b.id
                    AND uze_deleted = 0";

        return $this->db->fetchAllAssociative($sql, ['uzsId' => $uzsId]);
    }

    public function getEiluteById(int $uzeId): ?array
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
                    uze_gam_plocio_sutikimas,
                    min_width, 
                    max_warranty_width,
                    uze_gaminio_aukstis,
                    uze_atitraukimo_kaladele,
                    uze_vyriai,
                    uze_gaminio_spalva_id,
                    uze_lameliu_spalva_id,
                    material.wholesale_name AS uze_lameles_spalva,
                    uze_medziagos_plotis,
                    uze_medziagos_aukstis,
                    uze_stabdymo_mechanizmas,
                    uze_valdymas_puse,
                    uze_komentarai_gamybai,
                    uze_montavimas_i,
                    uze_virsnisos_cm,
                    uze_valo_itempimas,
                    uze_apatinio_prof_fiksacija
                FROM 
                    uzsakymo_eilutes
                INNER JOIN uzsakymai ON uzs_id = uze_uzs_id
                INNER JOIN ord_roller_mechanism a ON uze_id_mechanism = a.id
                INNER JOIN ord_product b ON a.id_product = b.id
                LEFT JOIN ord_roller_material material ON material.id = uze_lameliu_spalva_id
                WHERE uze_id = :uzeId";

        return $this->db->fetchAssociative($sql, ['uzeId' => $uzeId]) ?: null;
    }

    public function getPrice(array $duomenys): array
    {
        // 1. SET pradinių reikšmių OUT kintamiesiems
        $this->db->executeStatement('
            SET @p_kaina_be_pvm = NULL,
                @p_kaina_su_pvm = NULL,
                @p_klaida = NULL
        ');

        // 2. Paruošiam parametrus
       $params = [
    'id_product' => $duomenys['id_product'] ?? null,
    'id_mechanism' => $duomenys['id_mechanism'] ?? null,
    'id_color' => !empty($duomenys['id_color']) ? (int)$duomenys['id_color'] : null,
    'id_material' => ($duomenys['id_material'] ?? '') === '' ? null : $duomenys['id_material'],
    'heigth' => isset($duomenys['heigth']) && $duomenys['heigth'] !== '' ? (int)$duomenys['heigth'] : null,
    'width'  => isset($duomenys['width']) && $duomenys['width'] !== '' ? (int)$duomenys['width'] : null,
    'tipas' => !empty($duomenys['tipas']) ? (string)$duomenys['tipas'] : null,
    ];

        // 3. CALL procedūrą
        $sql = "CALL skaiciuoti_kaina(:id_product, :id_mechanism, :id_color, :id_material,:heigth, :width,:tipas, @p_kaina_be_pvm, @p_kaina_su_pvm,  @p_klaida)";
        $this->db->executeStatement($sql, $params);

        // 4. Gauti OUT reikšmes
        $rez = $this->db->fetchAssociative("
            SELECT
                @p_kaina_be_pvm AS kaina_be_pvm,
                @p_kaina_su_pvm AS kaina_su_pvm,                
                @p_klaida AS klaida
        ");
//dd($rez);
        return $rez;
    }



}
