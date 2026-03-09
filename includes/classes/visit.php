<?php
class Visit
{
    private $conn;
    private $table = "medical_visits";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // 1. Generate Unique Visit ID (VST-YEAR-XXXX)
    // 3. Get History for a Specific Patient


    // Add to includes/classes/Visit.php
// New countAll with Role-based filtering
    public function countAll($role = '', $uid = '')
    {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'active'";

        if ($role == 'Doctor') {
            $sql .= " AND assigned_doctor_id = :uid";
        } elseif ($role == 'Nurse') {
            $sql .= " AND assigned_nurse_id = :uid";
        }

        $stmt = $this->conn->prepare($sql);
        if ($role == 'Doctor' || $role == 'Nurse') {
            $stmt->bindParam(':uid', $uid);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // New getPaginated with Role-based filtering
    public function getPaginated($limit, $offset, $role = '', $uid = '')
    {
        $sql = "SELECT v.*, p.full_name, p.medical_record_number 
                FROM " . $this->table . " v
                JOIN patients p ON v.patient_id = p.patient_id 
                WHERE v.status = 'active'";

        if ($role == 'Doctor') {
            $sql .= " AND v.assigned_doctor_id = :uid";
        } elseif ($role == 'Nurse') {
            $sql .= " AND v.assigned_nurse_id = :uid";
        }

        $sql .= " ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        if ($role == 'Doctor' || $role == 'Nurse') {
            $stmt->bindValue(':uid', $uid);
        }

        $stmt->execute();
        return $stmt;
    }

    // Optional: Get all visits (including completed) for history
    public function getAllPaginated($limit, $offset)
    {
        $query = "SELECT v.*, p.full_name, p.medical_record_number 
              FROM " . $this->table . " v
              JOIN patients p ON v.patient_id = p.patient_id 
              ORDER BY v.created_at DESC 
              LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Count all visits (including completed)
    public function countAllVisits()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    public function getHistory($patient_id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE patient_id = :pid ORDER BY visit_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $patient_id);
        $stmt->execute();
        return $stmt;
    }

   
  

    private function generateVisitID()
    {
        $year = date("Y");
        $query = "SELECT visit_id FROM " . $this->table . " WHERE visit_id LIKE 'VST-$year-%' ORDER BY visit_id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $last_id = $row['visit_id'];
            $number = intval(substr($last_id, 9)) + 1;
        } else {
            $number = 1;
        }
        return "VST-$year-" . str_pad($number, 4, "0", STR_PAD_LEFT);
    }

    public function create($patient_id, $type, $notes)
    {
        try {
            $visit_id = $this->generateVisitID();
            $date = date("Y-m-d");

            $query = "INSERT INTO " . $this->table . " 
                      (visit_id, patient_id, visit_date, visit_type, clinical_notes) 
                      VALUES (:vid, :pid, :vdate, :vtype, :notes)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':vid', $visit_id);
            $stmt->bindParam(':pid', $patient_id);
            $stmt->bindParam(':vdate', $date);
            $stmt->bindParam(':vtype', $type);
            $stmt->bindParam(':notes', $notes);

            return $stmt->execute() ? $visit_id : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAll($limit = 10)
    {
        $query = "SELECT v.*, p.full_name, p.medical_record_number 
                  FROM " . $this->table . " v
                  JOIN patients p ON v.patient_id = p.patient_id 
                  ORDER BY v.created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }



    // Update Triage Level (Nurse)
    public function setTriage($visit_id, $level)
    {
        $stmt = $this->conn->prepare("UPDATE medical_visits SET triage_level = ? WHERE visit_id = ?");
        return $stmt->execute([$level, $visit_id]);
    }

    // Update Visit Type (Doctor - e.g., to Inpatient)
    public function updateType($visit_id, $type)
    {
        $stmt = $this->conn->prepare("UPDATE medical_visits SET visit_type = ? WHERE visit_id = ?");
        return $stmt->execute([$type, $visit_id]);
    }
}

