<?php
class Patient
{
    private $conn;
    private $table = "patients";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function countAll($searchTerm = "")
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        if (!empty($searchTerm)) {
            $query .= " WHERE first_name LIKE :q OR last_name LIKE :q OR mrn LIKE :q OR phone LIKE :q";
        }
        $stmt = $this->conn->prepare($query);
        if (!empty($searchTerm)) {
            $q = "%$searchTerm%";
            $stmt->bindParam(':q', $q);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getPaginated($limit, $offset, $searchTerm = "")
    {
        $query = "SELECT * FROM " . $this->table;
        if (!empty($searchTerm)) {
            $query .= " WHERE first_name LIKE :q OR last_name LIKE :q OR mrn LIKE :q OR phone LIKE :q";
        }
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        if (!empty($searchTerm)) {
            $q = "%$searchTerm%";
            $stmt->bindValue(':q', $q);
        }

        $stmt->execute();
        return $stmt;
    }
    public function advancedSearch($term, $gender = '', $ageRange = '')
    {
        $sql = "SELECT * FROM patients WHERE (full_name LIKE :term OR medical_record_number LIKE :term OR contact_details LIKE :term)";

        if ($gender)
            $sql .= " AND gender = :gender";
        // Age range logic example: "0-18", "19-60", "60+"
        if ($ageRange == 'child')
            $sql .= " AND age < 18";
        if ($ageRange == 'adult')
            $sql .= " AND age BETWEEN 18 AND 60";
        if ($ageRange == 'senior')
            $sql .= " AND age > 60";

        $sql .= " ORDER BY full_name ASC LIMIT 20";
        $stmt = $this->conn->prepare($sql);
        $term = "%$term%";
        $stmt->bindParam(':term', $term);
        if ($gender)
            $stmt->bindParam(':gender', $gender);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // 1. Generate Unique MRN (Format: MRN-2026-0001)
    private function generateMRN()
    {
        $year = date("Y");
        $query = "SELECT medical_record_number FROM " . $this->table . " WHERE medical_record_number LIKE 'MRN-$year-%' ORDER BY medical_record_number DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $last_mrn = $row['medical_record_number'];
            $number = intval(substr($last_mrn, 9)) + 1;
        } else {
            $number = 1;
        }
        return "MRN-$year-" . str_pad($number, 4, "0", STR_PAD_LEFT);
    }

    // 2. Duplicate Detection (Name + Contact Check)
    public function isDuplicate($name, $contact)
    {
        $query = "SELECT COUNT(*) FROM " . $this->table . " WHERE full_name = :name AND contact_details = :contact";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact', $contact);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // 3. Register New Patient
    public function register($data)
    {
        if ($this->isDuplicate($data['full_name'], $data['contact_details'])) {
            return "duplicate";
        }

        $mrn = $this->generateMRN();
        $id = "PAT-" . substr(uniqid(), -5); // Simple unique ID
        $reg_date = date("Y-m-d");

        $query = "INSERT INTO " . $this->table . " 
                  (patient_id, medical_record_number, full_name, age, gender, address, contact_details, registered_date, created_at) 
                  VALUES (:id, :mrn, :name, :age, :gender, :address, :contact, :rdate, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':mrn', $mrn);
        $stmt->bindParam(':name', $data['full_name']);
        $stmt->bindParam(':age', $data['age']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':contact', $data['contact_details']);
        $stmt->bindParam(':rdate', $reg_date);

        if ($stmt->execute())
            return $mrn;
        return false;
    }

    // 4. Search & List
    public function search($term = "")
    {
        $query = "SELECT * FROM " . $this->table;
        if (!empty($term)) {
            $query .= " WHERE full_name LIKE :term OR medical_record_number LIKE :term";
        }
        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        if (!empty($term)) {
            $search_term = "%$term%";
            $stmt->bindParam(':term', $search_term);
        }
        $stmt->execute();
        return $stmt;
    }

    // 5. Count Search Results
    public function countSearch($term = "")
    {
        $query = "SELECT COUNT(*) FROM " . $this->table;
        if (!empty($term)) {
            $query .= " WHERE full_name LIKE :term OR medical_record_number LIKE :term";
        }
        $stmt = $this->conn->prepare($query);
        if (!empty($term)) {
            $search_term = "%$term%";
            $stmt->bindParam(':term', $search_term);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // 6. Search with Pagination
    public function searchPaginated($term = "", $limit = 10, $offset = 0)
    {
        $query = "SELECT * FROM " . $this->table;
        if (!empty($term)) {
            $query .= " WHERE full_name LIKE :term OR medical_record_number LIKE :term";
        }
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        if (!empty($term)) {
            $search_term = "%$term%";
            $stmt->bindParam(':term', $search_term);
        }
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // 7. Get Single Patient by ID
    public function getById($id)
    {
        $stmt = $this->conn->prepare("SELECT *, created_at FROM " . $this->table . " WHERE patient_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 8. Update Patient (Original method - kept for backward compatibility)
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table . " SET 
                  full_name = :name, age = :age, gender = :gender, 
                  address = :address, contact_details = :contact 
                  WHERE patient_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['full_name']);
        $stmt->bindParam(':age', $data['age']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':contact', $data['contact_details']);
        return $stmt->execute();
    }

    // 9. NEW: Update Patient with array data
    public function updatePatient($data)
    {
        $query = "UPDATE " . $this->table . " SET 
                  full_name = :name, 
                  age = :age, 
                  gender = :gender, 
                  contact_details = :contact, 
                  address = :address 
                  WHERE patient_id = :pid";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['full_name']);
        $stmt->bindParam(':age', $data['age']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':contact', $data['contact_details']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':pid', $data['patient_id']);

        return $stmt->execute();
    }

    // 10. NEW: Delete Patient (only if no visits exist)
    public function deletePatient($patient_id)
    {
        // First check if patient has any visits
        $check = $this->conn->prepare("SELECT COUNT(*) FROM medical_visits WHERE patient_id = ?");
        $check->execute([$patient_id]);
        $count = $check->fetchColumn();

        if ($count > 0) {
            return false; // Cannot delete patient with visit history
        }

        $query = "DELETE FROM " . $this->table . " WHERE patient_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $patient_id);
        return $stmt->execute();
    }

    // 11. NEW: Get Patient Creation Time for Edit Window
    public function getPatientCreationTime($patient_id)
    {
        $query = "SELECT created_at FROM " . $this->table . " WHERE patient_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $patient_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['created_at'] : null;
    }

    // 12. NEW: Check if patient can be edited (within 30 minutes)
    public function canEdit($patient_id)
    {
        $created_at = $this->getPatientCreationTime($patient_id);
        if (!$created_at)
            return false;

        $createdTime = strtotime($created_at);
        $diffMinutes = round((time() - $createdTime) / 60);

        return $diffMinutes <= 30;
    }
}