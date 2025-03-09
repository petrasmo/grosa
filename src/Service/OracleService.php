<?php

namespace App\Service;


class OracleService
{
    private $connection;

    public function __construct()
    {
        // Gauname prisijungimo duomenis iš .env
        $username = $_ENV['ORACLE_USERNAME'];
        $password = $_ENV['ORACLE_PASSWORD'];
        $host = $_ENV['ORACLE_HOST'];
        $port = $_ENV['ORACLE_PORT'];
        $serviceName = $_ENV['ORACLE_SERVICE_NAME']; // ✅ Pakeitėme iš SID į SERVICE_NAME

        // Sukuriame prisijungimo stringą
        $dsn = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SERVICE_NAME=$serviceName)))";

        // Prisijungiame prie Oracle
        $this->connection = oci_connect($username, $password, $dsn, 'AL32UTF8');

        if (!$this->connection) {
            $error = oci_error();
            throw new \Exception('Nepavyko prisijungti prie Oracle: ' . $error['message']);
        }
    }

    // Metodas duomenims gauti
    public function fetchData($query, $params = [])
    {
        $stmt = oci_parse($this->connection, $query);

        foreach ($params as $key => $val) {
            oci_bind_by_name($stmt, $key, $params[$key]);
        }

        oci_execute($stmt);

        $result = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $result[] = $row;
        }

        oci_free_statement($stmt);

        return $result;
    }

    // Metodas duomenims įrašyti / atnaujinti
    public function executeQuery($query, $params = [])
    {
        $stmt = oci_parse($this->connection, $query);

        foreach ($params as $key => &$val) {
            if (is_array($val)) {
                // Jei parametras perduotas kaip masyvas (pvz., OUT parametras su dydžiu)
                oci_bind_by_name($stmt, $key, $val['value'], $val['size']);
            } else {
                // Paprastas IN parametras
                oci_bind_by_name($stmt, $key, $val);
            }
        }
     

        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        oci_free_statement($stmt);

        return $result;
    }


    // Metodas atsijungti nuo DB
    public function __destruct()
    {
        if ($this->connection) {
            oci_close($this->connection);
        }
    }
}