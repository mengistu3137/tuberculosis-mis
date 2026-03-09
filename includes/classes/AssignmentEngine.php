<?php
class AssignmentEngine
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Algorithm: Find the staff member with the LEAST active tasks
     */

    public function triggerFullReassignment($source_uid, $role)
    {
        switch ($role) {
            case 'Doctor':
                return $this->migrateDoctorWorkload($source_uid);
            case 'Nurse':
                return $this->migrateNurseWorkload($source_uid);
            case 'Lab Technician':
                return $this->migrateLabWorkload($source_uid);
            case 'Radiologist':
                return $this->migrateRadWorkload($source_uid);
        }
        return true;
    }

    private function migrateDoctorWorkload($uid)
    {
        // Find all 'active' visits assigned to this doctor
        $stmt = $this->conn->prepare("SELECT visit_id FROM medical_visits WHERE assigned_doctor_id = ? AND status = 'active'");
        $stmt->execute([$uid]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as $t) {
            $this->autoAssignDoctor($t['visit_id']); // This method already picks the least loaded
        }
        return true;
    }

    private function migrateNurseWorkload($uid)
    {
        $stmt = $this->conn->prepare("SELECT visit_id FROM medical_visits WHERE assigned_nurse_id = ? AND status = 'active'");
        $stmt->execute([$uid]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as $t) {
            $this->autoAssignNurse($t['visit_id']);
        }
        return true;
    }

    private function migrateLabWorkload($uid)
    {
        $stmt = $this->conn->prepare("SELECT request_id FROM lab_requests WHERE assigned_tech_id = ? AND status = 'pending'");
        $stmt->execute([$uid]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as $t) {
            $this->autoAssignLabTech($t['request_id']);
        }
        return true;
    }

    private function migrateRadWorkload($uid)
    {
        $stmt = $this->conn->prepare("SELECT request_id FROM radiology_requests WHERE assigned_rad_id = ? AND status = 'pending'");
        $stmt->execute([$uid]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as $t) {
            $this->autoAssignRadiologist($t['request_id']);
        }
        return true;
    }
    private function findLeastLoadedStaff($role)
    {
        // Query to find active staff of a specific role and count their current assigned tasks
        // We only look for staff who are 'active'
        $query = "SELECT u.user_id, COUNT(v.visit_id) as current_load 
                  FROM users u 
                  LEFT JOIN medical_visits v ON u.user_id = v.assigned_doctor_id 
                  -- Logic: Only count visits that are NOT yet discharged/completed
                  WHERE u.role = :role AND u.status = 'active'
                  GROUP BY u.user_id 
                  ORDER BY current_load ASC 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->execute();

        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        return $staff ? $staff['user_id'] : null;
    }

    // 1. Auto-Assign Doctor during Clerk Check-in
    public function autoAssignDoctor($visit_id)
    {
        $doctorId = $this->findLeastLoadedStaff('Doctor');
        if ($doctorId) {
            $stmt = $this->conn->prepare("UPDATE medical_visits SET assigned_doctor_id = ? WHERE visit_id = ?");
            return $stmt->execute([$doctorId, $visit_id]);
        }
        return false;
    }

    // 2. Auto-Assign Nurse for Triage/Support
    public function autoAssignNurse($visit_id)
    {
        $nurseId = $this->findLeastLoadedStaff('Nurse');
        if ($nurseId) {
            $stmt = $this->conn->prepare("UPDATE medical_visits SET assigned_nurse_id = ? WHERE visit_id = ?");
            return $stmt->execute([$nurseId, $visit_id]);
        }
        return false;
    }

    // 3. Auto-Assign Lab Technician
    public function autoAssignLabTech($request_id)
    {
        $query = "SELECT u.user_id, COUNT(r.request_id) as current_load 
                  FROM users u 
                  LEFT JOIN lab_requests r ON u.user_id = r.assigned_tech_id 
                  WHERE u.role = 'Lab Technician' AND u.status = 'active' AND (r.status = 'pending' OR r.status IS NULL)
                  GROUP BY u.user_id ORDER BY current_load ASC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $tech = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tech) {
            $stmt = $this->conn->prepare("UPDATE lab_requests SET assigned_tech_id = ? WHERE request_id = ?");
            return $stmt->execute([$tech['user_id'], $request_id]);
        }
        return false;
    }

    // 4.  Auto-Assign Radiologist (UC-07 Variant)
    public function autoAssignRadiologist($request_id)
    {
        // Logic: Scan radiologists, count current 'pending' or 'processing' scans
        $query = "SELECT u.user_id, COUNT(r.request_id) as current_load 
                  FROM users u 
                  LEFT JOIN radiology_requests r ON u.user_id = r.assigned_rad_id 
                  WHERE u.role = 'Radiologist' AND u.status = 'active' AND (r.status != 'completed' OR r.status IS NULL)
                  GROUP BY u.user_id ORDER BY current_load ASC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rad = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rad) {
            $stmt = $this->conn->prepare("UPDATE radiology_requests SET assigned_rad_id = ? WHERE request_id = ?");
            return $stmt->execute([$rad['user_id'], $request_id]);
        }
        return false;
    }

    // 5. Auto-Assign Pharmacist
    public function autoAssignPharmacist($prescription_id)
    {
        // Logic: Scan pharmacists, count currently undispensed prescriptions assigned to them
        $query = "SELECT u.user_id, COUNT(p.prescription_id) as current_load 
                  FROM users u 
                  LEFT JOIN prescriptions p ON u.user_id = p.assigned_phr_id 
                  WHERE u.role = 'Pharmacist' AND u.status = 'active' AND (p.is_dispensed = 0 OR p.is_dispensed IS NULL)
                  GROUP BY u.user_id ORDER BY current_load ASC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $phr = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($phr) {
            $stmt = $this->conn->prepare("UPDATE prescriptions SET assigned_phr_id = ? WHERE prescription_id = ?");
            return $stmt->execute([$phr['user_id'], $prescription_id]);
        }
        return false;
    }
}