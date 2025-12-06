<?php
require_once __DIR__ . '/../config/database.php';

class BeneficiaryService {
    private $db;

    public function __construct() {
        $this->db = new Binali\Config\Database();
    }

    public function listByUser($userId) {
        $query = "SELECT id, sId, name, phone FROM beneficiary WHERE sId = ? ORDER BY name ASC";
        return $this->db->query($query, [$userId]);
    }

    public function create($userId, $name, $phone) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO beneficiary (sId, name, phone) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $name, $phone]);
        return $conn->lastInsertId();
    }

    public function update($id, $userId, $name, $phone) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE beneficiary SET name = ?, phone = ? WHERE id = ? AND sId = ?");
        return $stmt->execute([$name, $phone, $id, $userId]);
    }

    public function delete($id, $userId) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("DELETE FROM beneficiary WHERE id = ? AND sId = ?");
        return $stmt->execute([$id, $userId]);
    }
}

?>
