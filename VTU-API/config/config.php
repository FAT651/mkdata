<?php
namespace Binali\Config;

use PDO;
use PDOException;
use Exception;

class Config {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getMonnifyConfig() {
        try {
            error_log("Attempting to get Monnify configuration");
            
            $query = "SELECT name, value FROM apiconfigs WHERE name IN ('monifyApi', 'monifySecrete', 'monifyContract')";
            $result = $this->db->query($query);
            
            if (!$result) {
                error_log("No configuration found in database");
                throw new Exception('Monnify configuration not found');
            }
            
            $config = [];
            
            $config = [];
            foreach ($result as $row) {
                $config[$row['name']] = $row['value'];
            }
            
            error_log("Monnify config retrieved. Keys found: " . implode(", ", array_keys($config)));
            
            $monnifyConfig = [
                'api_key' => $config['monifyApi'] ?? '',
                'secret_key' => $config['monifySecrete'] ?? '',
                'contract_code' => $config['monifyContract'] ?? ''
            ];
            
            // Validate configuration
            if (empty($monnifyConfig['api_key']) || empty($monnifyConfig['secret_key']) || empty($monnifyConfig['contract_code'])) {
                error_log("Incomplete Monnify configuration found");
                throw new Exception('Incomplete Monnify configuration');
            }
            
            return $monnifyConfig;
        } catch (Exception $e) {
            error_log("Error in getMonnifyConfig: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve Strowallet configuration from apiconfigs table.
     * Expects keys: 'strowalletApi' and 'strowalletBase' to be present.
     */
    public function getStrowalletConfig() {
        try {
            error_log("Attempting to get Strowallet configuration");

            $query = "SELECT name, value FROM apiconfigs WHERE name IN ('strowalletApi', 'strowalletBase', 'strowalletMode', 'strowalletWebhook')";
            $result = $this->db->query($query);

            if (!$result) {
                error_log("No Strowallet configuration found in database");
                throw new Exception('Strowallet configuration not found');
            }

            $config = [];
            foreach ($result as $row) {
                $config[$row['name']] = $row['value'];
            }

            $strowalletConfig = [
                'api_key' => $config['strowalletApi'] ?? '',
                'base_url' => $config['strowalletBase'] ?? '',
                'mode' => $config['strowalletMode'] ?? 'sandbox',
                'webhook' => $config['strowalletWebhook'] ?? ''
            ];

            if (empty($strowalletConfig['api_key']) || empty($strowalletConfig['base_url'])) {
                error_log("Incomplete Strowallet configuration found");
                throw new Exception('Incomplete Strowallet configuration');
            }

            return $strowalletConfig;
        } catch (Exception $e) {
            error_log("Error in getStrowalletConfig: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
