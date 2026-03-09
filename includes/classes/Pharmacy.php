<?php
class Pharmacy {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Fetch all prescriptions that have NOT been dispensed yet
    public function getPendingPrescriptions() {
        $query = "SELECT pr.*, p.full_name, p.medical_record_number, u.full_name as prescribed_by 
FROM prescriptions pr
JOIN medical_visits v ON pr.visit_id = v.visit_id
JOIN patients p ON v.patient_id = p.patient_id
JOIN users u ON pr.prescribed_by = u.user_id
WHERE pr.is_dispensed = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 2. Mark prescription as dispensed and record in dispensing_records
    public function dispenseMedication($prescription_id, $pharmacist_id) {
        $dispense_id = "DSP-" . strtoupper(substr(md5(uniqid()), 0, 6));
        
        try {
            $this->conn->beginTransaction();

            // A. Insert into dispensing_records
            $stmt1 = $this->conn->prepare("INSERT INTO dispensing_records (dispense_id, prescription_id, pharmacist_id) VALUES (?, ?, ?)");
            $stmt1->execute([$dispense_id, $prescription_id, $pharmacist_id]);

            // B. Update prescription status
            $stmt2 = $this->conn->prepare("UPDATE prescriptions SET is_dispensed = 1 WHERE prescription_id = ?");
            $stmt2->execute([$prescription_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}