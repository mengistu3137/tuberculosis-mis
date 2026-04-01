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
    public function countAll($role = '', $uid = '', $filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE status = 'active'";

        if (!empty($filters['today'])) {
            $sql .= " AND DATE(created_at) = CURRENT_DATE";
        }

        if (!empty($filters['visit_type'])) {
            $sql .= " AND visit_type = :visit_type";
        }

        if ($role === 'Doctor') {
            $sql .= " AND assigned_doctor_id = :uid";
        } elseif ($role === 'Nurse') {
            $sql .= " AND assigned_nurse_id = :uid";
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($filters['visit_type'])) {
            $stmt->bindParam(':visit_type', $filters['visit_type']);
        }
        if ($role === 'Doctor' || $role === 'Nurse') {
            $stmt->bindParam(':uid', $uid);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // New getPaginated with Role-based filtering and doctor/nurse/ward enrichment
    public function getPaginated($limit, $offset, $role = '', $uid = '', $filters = [])
    {
        $sql = "SELECT v.*, p.full_name, p.medical_record_number, 
                   d.full_name AS doctor_name, d.email AS doctor_email,
                   n.full_name AS nurse_name,
                   w.assignment_location AS ward_location, w.assigned_at AS ward_assigned_at,
                   r.status AS ward_request_status
            FROM " . $this->table . " v
            JOIN patients p ON v.patient_id = p.patient_id
            LEFT JOIN users d ON v.assigned_doctor_id = d.user_id
            LEFT JOIN users n ON v.assigned_nurse_id = n.user_id
            LEFT JOIN visit_ward_assignments w ON v.visit_id = w.visit_id
            LEFT JOIN visit_ward_assignment_requests r ON v.visit_id = r.visit_id
            WHERE v.status = 'active'";

        if (!empty($filters['today'])) {
            $sql .= " AND DATE(v.created_at) = CURRENT_DATE";
        }

        if (!empty($filters['visit_type'])) {
            $sql .= " AND v.visit_type = :visit_type";
        }

        if ($role === 'Doctor') {
            $sql .= " AND v.assigned_doctor_id = :uid";
        } elseif ($role === 'Nurse') {
            $sql .= " AND v.assigned_nurse_id = :uid";
        }

        $sql .= " ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        if (!empty($filters['visit_type'])) {
            $stmt->bindValue(':visit_type', $filters['visit_type']);
        }
        if ($role === 'Doctor' || $role === 'Nurse') {
            $stmt->bindValue(':uid', $uid);
        }

        $stmt->execute();
        return $stmt;
    }

    // Optional: Get all visits (including completed) for history with filters and staff scoping
    public function getAllPaginated($limit, $offset, $role = '', $uid = '', $filters = [])
    {
        $query = "SELECT v.*, p.full_name, p.medical_record_number,
                         d.full_name AS doctor_name, n.full_name AS nurse_name,
                         d.email AS doctor_email, n.email AS nurse_email,
                         dis.discharged_at, w.assignment_location AS ward_location, w.assigned_at AS ward_assigned_at
                  FROM " . $this->table . " v
                  JOIN patients p ON v.patient_id = p.patient_id
                  LEFT JOIN users d ON v.assigned_doctor_id = d.user_id
                  LEFT JOIN users n ON v.assigned_nurse_id = n.user_id
                  LEFT JOIN (
                        SELECT visit_id, MAX(discharged_at) AS discharged_at
                        FROM discharges
                        GROUP BY visit_id
                  ) dis ON v.visit_id = dis.visit_id
                  LEFT JOIN visit_ward_assignments w ON v.visit_id = w.visit_id";

        $where = [];
        $params = [];

        if (!empty($filters['visit_type'])) {
            $where[] = "v.visit_type = :visit_type";
            $params[':visit_type'] = $filters['visit_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "v.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Scope history to doctor/nurse caseload (current or historical) when applicable
        if ($role === 'Doctor') {
            $where[] = "(v.assigned_doctor_id = :uid OR EXISTS (SELECT 1 FROM visit_care_team_history h WHERE h.visit_id = v.visit_id AND h.staff_id = :uid))";
            $params[':uid'] = $uid;
        } elseif ($role === 'Nurse') {
            $where[] = "(v.assigned_nurse_id = :uid OR EXISTS (SELECT 1 FROM visit_care_team_history h WHERE h.visit_id = v.visit_id AND h.staff_id = :uid))";
            $params[':uid'] = $uid;
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(' AND ', $where);
        }

        $query .= " ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $vParam) {
            $stmt->bindValue($k, $vParam);
        }
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Count all visits (including completed) with optional filters and scoping
    public function countAllVisits($role = '', $uid = '', $filters = [])
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " v";
        $where = [];
        $params = [];

        if (!empty($filters['visit_type'])) {
            $where[] = "v.visit_type = :visit_type";
            $params[':visit_type'] = $filters['visit_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "v.status = :status";
            $params[':status'] = $filters['status'];
        }

        if ($role === 'Doctor') {
            $where[] = "(v.assigned_doctor_id = :uid OR EXISTS (SELECT 1 FROM visit_care_team_history h WHERE h.visit_id = v.visit_id AND h.staff_id = :uid))";
            $params[':uid'] = $uid;
        } elseif ($role === 'Nurse') {
            $where[] = "(v.assigned_nurse_id = :uid OR EXISTS (SELECT 1 FROM visit_care_team_history h WHERE h.visit_id = v.visit_id AND h.staff_id = :uid))";
            $params[':uid'] = $uid;
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $vParam) {
            $stmt->bindValue($k, $vParam);
        }
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

    public function getWardAssignment($visit_id)
    {
        $sql = "SELECT w.assignment_location, w.assigned_at, u.full_name AS assigned_by_name
                FROM visit_ward_assignments w
                LEFT JOIN users u ON w.assigned_by = u.user_id
                WHERE w.visit_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$visit_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function upsertWardAssignment($visit_id, $location, $assignedBy)
    {
        $sql = "INSERT INTO visit_ward_assignments (visit_id, assignment_location, assigned_by)
                VALUES (:vid, :loc, :by)
                ON DUPLICATE KEY UPDATE assignment_location = VALUES(assignment_location), assigned_by = VALUES(assigned_by), updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':vid' => $visit_id,
            ':loc' => $location,
            ':by' => $assignedBy
        ]);
    }

    public function logCareTeamAssignment($visit_id, $staff_id, $role)
    {
        $sql = "INSERT INTO visit_care_team_history (visit_id, staff_id, role) VALUES (:vid, :sid, :role)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':vid' => $visit_id,
            ':sid' => $staff_id,
            ':role' => $role
        ]);
    }

    public function getCareTeamHistoryByVisit($visit_id)
    {
        $sql = "SELECT h.*, u.full_name, u.email
                FROM visit_care_team_history h
                LEFT JOIN users u ON h.staff_id = u.user_id
                WHERE h.visit_id = :vid
                ORDER BY h.assigned_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':vid', $visit_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

