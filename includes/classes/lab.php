<?php
/**
 * LABORATORY MANAGEMENT CLASS
 * Handles logic for Diagnostic Requests and Result Entry
 */

class Lab
{
    private $conn;
    private $requestTable = "lab_requests";
    private $resultTable = "lab_results";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    private function hasColumn($tableName, $columnName)
    {
        $query = "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':table_name' => $tableName,
            ':column_name' => $columnName
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }
    // 1. Count all requests (for pagination math)
    public function countAllRequests()
    {
        $query = "SELECT COUNT(*) FROM " . $this->requestTable . " WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // 2. Fetch paginated requests
    public function getPaginatedRequests($limit, $offset)
    {
        $query = "SELECT r.*, p.full_name as patient_name, u.full_name as doctor_name 
                  FROM " . $this->requestTable . " r
                  JOIN medical_visits v ON r.visit_id = v.visit_id
                  JOIN patients p ON v.patient_id = p.patient_id
                  JOIN users u ON r.doctor_id = u.user_id
                  WHERE r.status = 'pending'
                  ORDER BY r.created_at DESC 
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Note: Keep your existing getAllRequests() method if you use it for the "Pending" count logic globally

    /**
     * 1. Create a New Lab Request (Used by Doctors)
     */

    public function requestTest($visit_id, $test_type, $tb_test_category = null, $specimen_type = null)
    {
        // Security check for empty inputs
        if (empty($visit_id) || empty($test_type))
            return false;

        $request_id = "REQ-" . strtoupper(substr(md5(uniqid()), 0, 6));
        $date = date("Y-m-d");

        $query = "INSERT INTO " . $this->requestTable . " 
                  (request_id, visit_id, doctor_id, test_type, request_date, status) 
                  VALUES (:rid, :vid, :did, :ttype, :rdate, 'pending')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rid', $request_id);
        $stmt->bindParam(':vid', $visit_id);
        $stmt->bindParam(':did', $_SESSION['user_id']); // Doctor from session
        $stmt->bindParam(':ttype', $test_type);
        $stmt->bindParam(':rdate', $date);

        $saved = $stmt->execute();
        if (!$saved) {
            return false;
        }

        $tbColumns = [];
        $tbParams = [':id' => $request_id];
        if ($this->hasColumn($this->requestTable, 'tb_test_category')) {
            $tbColumns[] = "tb_test_category = :tb_test_category";
            $tbParams[':tb_test_category'] = $tb_test_category;
        }
        if ($this->hasColumn($this->requestTable, 'specimen_type')) {
            $tbColumns[] = "specimen_type = :specimen_type";
            $tbParams[':specimen_type'] = $specimen_type;
        }
        if (!empty($tbColumns)) {
            $update = "UPDATE " . $this->requestTable . " SET " . implode(', ', $tbColumns) . " WHERE request_id = :id";
            $updateStmt = $this->conn->prepare($update);
            $updateStmt->execute($tbParams);
        }

        return $request_id;
    }
   

    /**
     * 2. Fetch All Requests with Joined Patient & Doctor Names
     * Used for the main Laboratory Worklist UI
     */
    public function getAllRequests()
    {
        $query = "SELECT r.*, p.full_name as patient_name, u.full_name as doctor_name 
                  FROM " . $this->requestTable . " r
                  JOIN medical_visits v ON r.visit_id = v.visit_id
                  JOIN patients p ON v.patient_id = p.patient_id
                  JOIN users u ON r.doctor_id = u.user_id
                  ORDER BY r.status DESC, r.created_at DESC"; // Pending usually comes first

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 3. Fetch Single Request Details
     * Used for the Result Entry form
     */
    public function getRequestDetails($request_id)
    {
        $query = "SELECT r.*, p.full_name as patient_name, p.medical_record_number 
                  FROM " . $this->requestTable . " r
                  JOIN medical_visits v ON r.visit_id = v.visit_id
                  JOIN patients p ON v.patient_id = p.patient_id
                  WHERE r.request_id = :rid LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rid', $request_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 4. Submit Lab Result & Update Request Status
     * Uses Database Transaction to ensure data integrity
     */
    public function submitResult($request_id, $result_details)
    {
        $result_id = "RES-" . strtoupper(substr(md5(uniqid()), 0, 6));
        $date = date("Y-m-d");

        try {
            // Start Transaction
            $this->conn->beginTransaction();

            // A. Insert into lab_results
            $queryRes = "INSERT INTO " . $this->resultTable . " 
                         (result_id, request_id, technician_id, result_details, performed_date) 
                         VALUES (:resid, :reqid, :tid, :details, :pdate)";

            $stmt1 = $this->conn->prepare($queryRes);
            $stmt1->bindParam(':resid', $result_id);
            $stmt1->bindParam(':reqid', $request_id);
            $stmt1->bindParam(':tid', $_SESSION['user_id']); // Tech from session
            $stmt1->bindParam(':details', $result_details);
            $stmt1->bindParam(':pdate', $date);
            $stmt1->execute();

            // B. Update lab_requests status to 'completed'
            $queryReq = "UPDATE " . $this->requestTable . " SET status = 'completed' WHERE request_id = :reqid";
            $stmt2 = $this->conn->prepare($queryReq);
            $stmt2->bindParam(':reqid', $request_id);
            $stmt2->execute();

            // Commit all changes
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Rollback if any part fails
            $this->conn->rollBack();
            error_log("Lab Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 5. Get Results for a Specific Request
     * Used by Doctors to see findings
     */
    public function getResultsForRequest($request_id)
    {
        $query = "SELECT res.*, u.full_name as tech_name 
                  FROM " . $this->resultTable . " res
                  JOIN users u ON res.technician_id = u.user_id
                  WHERE res.request_id = :rid LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rid', $request_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}