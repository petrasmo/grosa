<?php
namespace App\Repository;

use Doctrine\DBAL\Connection;


class KainynasRepository
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function gautiVisusIrasaus(): array
    {
        $sql = "select kai_id, b.name as gaminys, c.name as gaminio_tipas, e.name as spalva,
        kai_roller_width , d.name medziaga, kai_kaina_su_pvm
 from kainynas a
	LEFT JOIN ord_product b ON b.id = a.kai_id_product  -- a.name
    LEFT JOIN ord_roller_mechanism c ON c.id = a.kai_id_mechanism  -- a.name
    LEFT JOIN ord_roller_material d ON d.id = a.kai_id_material  -- a.name
    left join ord_color e on e.id=a.kai_id_color -- e.name
    where kai_deleted=0
    order by b.name,c.name,e.name,d.name
    LIMIT 1000";
        return $this->db->fetchAllAssociative($sql);
    }

    public function ieskotiKainu(array $filters): array
    {
       // dd('aaaaaaaaaaaaaaa');
        $sql = "
            SELECT 
                kai_id, 
                b.name as gaminys, 
                c.name as gaminio_tipas, 
                e.name as spalva,
                kai_roller_width, 
                d.name as medziaga, 
                kai_kaina_su_pvm
            FROM kainynas a
            LEFT JOIN ord_product b ON b.id = a.kai_id_product
            LEFT JOIN ord_roller_mechanism c ON c.id = a.kai_id_mechanism
            LEFT JOIN ord_roller_material d ON d.id = a.kai_id_material
            LEFT JOIN ord_color e ON e.id = a.kai_id_color
            WHERE kai_deleted = 0
        ";
    
        $params = [];
    
        if (!empty($filters['gaminys'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(b.name)), ' ', '') LIKE REPLACE(UPPER(TRIM(:gaminys)), ' ', '')";
            $params['gaminys'] = '%' . trim($filters['gaminys']) . '%';
        }
        if (!empty($filters['tipas'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(c.name)), ' ', '') LIKE REPLACE(UPPER(TRIM(:tipas)), ' ', '')";
            $params['tipas'] = '%' . trim($filters['tipas']) . '%';
        }
        if (!empty($filters['spalva'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(e.name)), ' ', '') LIKE REPLACE(UPPER(TRIM(:spalva)), ' ', '')";
            $params['spalva'] = '%' . trim($filters['spalva']) . '%';
        }
        if (!empty($filters['medziaga'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(d.name)), ' ', '') LIKE REPLACE(UPPER(TRIM(:medziaga)), ' ', '')";
            $params['medziaga'] = '%' . trim($filters['medziaga']) . '%';
        }
    
        $sql .= " ORDER BY b.name, c.name, e.name, d.name LIMIT 1000";
    
        $stmt = $this->db->prepare($sql);
    
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    
        return $stmt->executeQuery()->fetchAllAssociative();
    }
    

    

    public function issaugotiKeliasKainas(array $kainos): void
    {
        foreach ($kainos as $kainaData) {
            if (!isset($kainaData['kai_id'], $kainaData['kaina'])) {
                continue; // Jei trūksta duomenų – skipinam
            }

            $sql = "UPDATE kainynas SET kai_kaina_su_pvm = :kaina WHERE kai_id = :kai_id";
            $this->db->executeStatement($sql, [
                'kaina' => (float)$kainaData['kaina'],
                'kai_id' => (int)$kainaData['kai_id'],
            ]);
        }
    }


    public function ieskotiKainuTaisykliu(array $filters): array
    {
        $sql = "
            SELECT 
                kat_id,
                gaminys,
                gaminio_tipas,
                spalva,
                medziaga,
                kat_kaina,
                kat_aprasymas,
                kat_matavimo_vienetas
            FROM view_kainu_taisykles a
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['gaminys'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(gaminys)), ' ', '') LIKE REPLACE(UPPER(TRIM(:gaminys)), ' ', '')";
            $params['gaminys'] = '%' . trim($filters['gaminys']) . '%';
        }
        if (!empty($filters['tipas'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(gaminio_tipas)), ' ', '') LIKE REPLACE(UPPER(TRIM(:tipas)), ' ', '')";
            $params['tipas'] = '%' . trim($filters['tipas']) . '%';
        }
        if (!empty($filters['spalva'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(spalva)), ' ', '') LIKE REPLACE(UPPER(TRIM(:spalva)), ' ', '')";
            $params['spalva'] = '%' . trim($filters['spalva']) . '%';
        }
        if (!empty($filters['medziaga'])) {
            $sql .= " AND REPLACE(UPPER(TRIM(medziaga)), ' ', '') LIKE REPLACE(UPPER(TRIM(:medziaga)), ' ', '')";
            $params['medziaga'] = '%' . trim($filters['medziaga']) . '%';
        }

        $sql .= " ORDER BY gaminys, gaminio_tipas, spalva, medziaga LIMIT 1000";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->executeQuery()->fetchAllAssociative();
    }
}