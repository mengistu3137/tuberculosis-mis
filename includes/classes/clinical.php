<?php
class Clinical
{
    private $conn;

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

    private function hasTable($tableName)
    {
        $query = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':table_name' => $tableName]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createDetailedReferral($data, $doctor_id)
    {
        // Prevent invalid visit IDs
        if ($data['visit_id'] === "NO_ACTIVE_VISIT" || empty($data['visit_id']))
            return false;

        $id = "REF-" . strtoupper(substr(md5(uniqid()), 0, 6));
        $referral_date = date('Y-m-d');

        // Prepare JSON data - MODIFIED to match dynamic form
        $diagnoses_json = json_encode([
            'items' => $data['diagnosis_items'] ?? [], // Dynamic array from form
            'other' => $data['other_diagnoses'] ?? ''
        ]);

        $treatments_json = json_encode([
            'initiated' => $data['treatments_initiated'] ?? '',
            'items' => $data['treatment_items'] ?? [], // Dynamic array from form
            'medication_chart_attached' => isset($data['medication_chart_attached']) ? true : false
        ]);

        $functional_status_json = json_encode([
            'mobility' => [
                'status' => $data['mobility_status'] ?? '',
                'precautions' => $data['mobility_precautions'] ?? ''
            ],
            'self_care' => $data['self_care_status'] ?? '',
            'cognitive_impairment' => isset($data['cognitive_impairment']) ? true : false,
            'assistive_devices' => [
                'provided' => $data['assistive_devices_provided'] ?? '',
                'required' => $data['assistive_devices_required'] ?? ''
            ]
        ]);

        $query = "INSERT INTO referrals (
        referral_id, patient_id, visit_id, date_of_birth, 
        referring_facility, referring_focal_point, referring_phone,
        target_facility, target_focal_point, target_phone,
        source_doctor_id, target_department, priority, referral_type, reason,
        transportation_needs, follow_up_requirements,
        diagnoses_json, treatments_json, functional_status_json,
        compiled_by, compiled_position, signature, status, referral_date
    ) VALUES (
        :id, :patient_id, :visit_id, :dob,
        :ref_facility, :ref_focal, :ref_phone,
        :target_facility, :target_focal, :target_phone,
        :doctor_id, :target_dept, :priority, :referral_type, :reason,
        :transport, :followup,
        :diagnoses_json, :treatments_json, :functional_json,
        :compiled_by, :compiled_position, :signature, 'Pending', :ref_date
    )";

        $stmt = $this->conn->prepare($query);

        // Bind parameters
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':patient_id', $data['patient_id']);
        $stmt->bindParam(':visit_id', $data['visit_id']);
        $stmt->bindParam(':dob', $data['date_of_birth']);

        $stmt->bindParam(':ref_facility', $data['referring_facility']);
        $stmt->bindParam(':ref_focal', $data['referring_focal_point']);
        $stmt->bindParam(':ref_phone', $data['referring_phone']);

        $stmt->bindParam(':target_facility', $data['target_facility']);
        $stmt->bindParam(':target_focal', $data['target_focal_point']);
        $stmt->bindParam(':target_phone', $data['target_phone']);

        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->bindParam(':target_dept', $data['target_department']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':referral_type', $data['referral_type']);
        $stmt->bindParam(':reason', $data['reason']);

        $stmt->bindParam(':transport', $data['transportation_needs']);
        $stmt->bindParam(':followup', $data['follow_up_requirements']);

        $stmt->bindParam(':diagnoses_json', $diagnoses_json);
        $stmt->bindParam(':treatments_json', $treatments_json);
        $stmt->bindParam(':functional_json', $functional_status_json);

        $stmt->bindParam(':compiled_by', $data['compiled_by']);
        $stmt->bindParam(':compiled_position', $data['compiled_position']);
        $stmt->bindParam(':signature', $data['signature']);
        $stmt->bindParam(':ref_date', $referral_date);

        return $stmt->execute();
    }

    public function getPendingLabRequests($visit_id)
    {
        $query = "SELECT lr.*, u.full_name as doctor_name
              FROM lab_requests lr
              LEFT JOIN users u ON lr.doctor_id = u.user_id
              WHERE lr.visit_id = :vid 
              AND lr.status = 'pending'
              ORDER BY 
                CASE WHEN lr.priority = 'STAT' THEN 0 ELSE 1 END,
                lr.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vid', $visit_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createReferral($data, $doctor_id)
    {
        // Prevent invalid visit IDs from crashing the DB
        if ($data['visit_id'] === "NO_ACTIVE_VISIT" || empty($data['visit_id']))
            return false;

        $id = "REF-" . strtoupper(substr(md5(uniqid()), 0, 6));
        $query = "INSERT INTO referrals 
              (referral_id, patient_id, visit_id, source_doctor_id, target_department, priority, reason, status) 
              VALUES (:id, :pid, :vid, :did, :target, :priority, :reason, 'Pending')";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':pid', $data['patient_id']);
        $stmt->bindParam(':vid', $data['visit_id']);
        $stmt->bindParam(':did', $doctor_id);
        $stmt->bindParam(':target', $data['target_dept']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':reason', $data['reason']);

        return $stmt->execute();
    }

    public function getPatientReferrals($patient_id)
    {
        $query = "SELECT r.*, u.full_name as doctor_name,
                  CASE 
                    WHEN r.priority = 'Emergency' THEN 'red'
                    WHEN r.priority = 'Urgent' THEN 'orange'
                    ELSE 'blue'
                  END as color
                  FROM referrals r
                  JOIN users u ON r.source_doctor_id = u.user_id
                  WHERE r.patient_id = :pid
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $patient_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== DISCHARGE METHODS ====================

    public function processDischarge($data, $user_id)
    {
        // Start transaction
        $this->conn->beginTransaction();

        try {
            $id = "DS-" . strtoupper(substr(md5(uniqid()), 0, 6));

            // Insert discharge record
            $query = "INSERT INTO discharges (
            discharge_id, patient_id, visit_id, summary_of_care, 
            condition_at_discharge, instructions, follow_up_date, discharged_by, clerical_processed
        ) VALUES (
            :id, :patient_id, :visit_id, :summary, 
            :condition, :instructions, :follow_up, :discharged_by, 1
        )";

            $stmt = $this->conn->prepare($query);
            $result1 = $stmt->execute([
                ':id' => $id,
                ':patient_id' => $data['patient_id'],
                ':visit_id' => $data['visit_id'],
                ':summary' => $data['summary'],
                ':condition' => $data['condition'],
                ':instructions' => $data['instructions'],
                ':follow_up' => $data['follow_up'],
                ':discharged_by' => $user_id
            ]);

            if (!$result1) {
                throw new Exception("Failed to insert discharge record");
            }

            // Update medical_visits status to 'completed'
            $updateVisit = $this->conn->prepare("UPDATE medical_visits SET status = 'completed', doctor_completed = 1 WHERE visit_id = ?");
            $result2 = $updateVisit->execute([$data['visit_id']]);

            if (!$result2) {
                throw new Exception("Failed to update visit status");
            }

            $this->conn->commit();

            // Log success
            error_log("Discharge successful for visit: " . $data['visit_id']);

            return $id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Discharge Error: " . $e->getMessage());
            return false;
        }
    }
    public function getDischargeDetails($visit_id)
    {
        $query = "SELECT d.*, u.full_name as discharged_by_name,
                  p.full_name as patient_name, p.medical_record_number
                  FROM discharges d
                  JOIN users u ON d.discharged_by = u.user_id
                  JOIN patients p ON d.patient_id = p.patient_id
                  WHERE d.visit_id = :vid
                  ORDER BY d.discharged_at DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vid', $visit_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==================== RADIOLOGY METHODS ====================

    public function requestRadiology($visit_id, $exam_type, $body_part = null, $clinical_history = null, $priority = 'normal')
    {
        if ($visit_id === "NO_ACTIVE_VISIT" || empty($visit_id)) {
            error_log("Radiology Error: Invalid visit_id - " . $visit_id);
            return false;
        }

        $id = "RAD-" . strtoupper(substr(md5(uniqid()), 0, 8)); // This is the ID we need
        $request_date = date("Y-m-d");

        $query = "INSERT INTO radiology_requests (
        request_id, visit_id, doctor_id, exam_type, body_part, 
        clinical_history, priority, status, request_date
    ) VALUES (
        :id, :vid, :did, :exam, :body, :history, :priority, 'pending', :rdate
    )";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':vid', $visit_id);
            $stmt->bindParam(':did', $_SESSION['user_id']);
            $stmt->bindParam(':exam', $exam_type);
            $stmt->bindParam(':body', $body_part);
            $stmt->bindParam(':history', $clinical_history);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindParam(':rdate', $request_date);

            $result = $stmt->execute();

            if ($result) {
                error_log("Radiology request created: " . $id);
                return $id; // MODIFIED: Return the ID string so Assignment Engine can use it
            }

            return false;
        } catch (PDOException $e) {
            error_log("Radiology Exception: " . $e->getMessage());
            return false;
        }
    }

    public function getRadiologyResults($visit_id)
    {
        $query = "SELECT rr.exam_type, rr.body_part, rr.priority,
                  res.findings, res.impression, res.image_path, res.performed_date,
                  u.full_name as radiologist_name
                  FROM radiology_requests rr
                  LEFT JOIN radiology_results res ON rr.request_id = res.request_id
                  LEFT JOIN users u ON res.radiologist_id = u.user_id
                  WHERE rr.visit_id = :vid
                  ORDER BY res.performed_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vid', $visit_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingRadiologyRequests($visit_id)
    {
        $query = "SELECT * FROM radiology_requests 
                  WHERE visit_id = :vid AND status = 'pending'
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vid', $visit_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // FETCH HISTORY (Updated to include created_at for time calculation)
    public function getPatientHistory($patient_id)
    {
        $query = "SELECT d.*, u.full_name as doctor FROM diagnoses d 
                  JOIN medical_visits v ON d.visit_id = v.visit_id 
                  JOIN users u ON d.doctor_id = u.user_id 
                  WHERE v.patient_id = :pid ORDER BY d.diagnosis_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $patient_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FETCH LAB RESULTS
    public function getVisitLabResults($visit_id)
    {
        $query = "SELECT r.test_type, res.result_details, res.performed_date, u.full_name as tech_name 
                  FROM lab_requests r 
                  JOIN lab_results res ON r.request_id = res.request_id 
                  JOIN users u ON res.technician_id = u.user_id 
                  WHERE r.visit_id = :vid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vid', $visit_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 1. Record Vital Signs
    public function recordVitals($data)
    {
        $id = "VIT-" . substr(md5(uniqid()), 0, 8);
        $query = "INSERT INTO vital_signs (vital_id, visit_id, temperature, blood_pressure, pulse, recorded_by) 
                  VALUES (:id, :vid, :temp, :bp, :pulse, :uid)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':vid', $data['visit_id']);
        $stmt->bindParam(':temp', $data['temperature']);
        $stmt->bindParam(':bp', $data['blood_pressure']);
        $stmt->bindParam(':pulse', $data['pulse']);
        $stmt->bindParam(':uid', $_SESSION['user_id']);

        return $stmt->execute();
    }

    // 2. Record Diagnosis (UC-05) - FIXED: Always INSERT new record, never UPDATE
    public function recordDiagnosis($data)
    {
        if ($data['visit_id'] === "NO_ACTIVE_VISIT")
            return false;

        // ALWAYS INSERT a new diagnosis record to maintain history
        $id = "DX-" . strtoupper(substr(md5(uniqid()), 0, 8));
        $query = "INSERT INTO diagnoses (diagnosis_id, visit_id, doctor_id, diagnosis_details, diagnosis_date, created_at) 
                  VALUES (:id, :vid, :uid, :details, :ddate, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':vid', $data['visit_id']);
        $stmt->bindParam(':uid', $_SESSION['user_id']);
        $stmt->bindParam(':details', $data['diagnosis_details']);

        $date = date("Y-m-d");
        $stmt->bindParam(':ddate', $date);

        $saved = $stmt->execute();
        if (!$saved) {
            return false;
        }

        // Persist TB-specific fields if migration columns exist.
        $tbColumns = [];
        $tbParams = [':id' => $id];

        if ($this->hasColumn('diagnoses', 'tb_classification')) {
            $tbColumns[] = "tb_classification = :tb_classification";
            $tbParams[':tb_classification'] = $data['tb_classification'] ?? null;
        }
        if ($this->hasColumn('diagnoses', 'tb_treatment_status')) {
            $tbColumns[] = "tb_treatment_status = :tb_treatment_status";
            $tbParams[':tb_treatment_status'] = $data['tb_treatment_status'] ?? null;
        }
        if ($this->hasColumn('diagnoses', 'mdr_tb_status')) {
            $tbColumns[] = "mdr_tb_status = :mdr_tb_status";
            $tbParams[':mdr_tb_status'] = $data['mdr_tb_status'] ?? 'No';
        }
        if ($this->hasColumn('diagnoses', 'diagnosis_method')) {
            $tbColumns[] = "diagnosis_method = :diagnosis_method";
            $tbParams[':diagnosis_method'] = $data['diagnosis_method'] ?? null;
        }

        if (!empty($tbColumns)) {
            $update = "UPDATE diagnoses SET " . implode(', ', $tbColumns) . " WHERE diagnosis_id = :id";
            $updateStmt = $this->conn->prepare($update);
            $updateStmt->execute($tbParams);
        }

        return true;
    }

    // --- NEW: Update Specific Diagnosis (for the Edit feature) ---
    public function updateDiagnosis($diagnosis_id, $details, $user_id)
    {
        $query = "UPDATE diagnoses SET diagnosis_details = :details, doctor_id = :uid WHERE diagnosis_id = :did";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':did', $diagnosis_id);
        return $stmt->execute();
    }

    // --- NEW: Delete Diagnosis ---
    public function deleteDiagnosis($diagnosis_id)
    {
        $query = "DELETE FROM diagnoses WHERE diagnosis_id = :did";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':did', $diagnosis_id);
        return $stmt->execute();
    }

    // --- UC-06 Create Treatment Plan ---
    public function saveTreatmentPlan($data)
    {
        // Ensure we have a diagnosis ID to link to
        if (!isset($data['diagnosis_id']) || empty($data['diagnosis_id'])) {
            return false;
        }

        // ALWAYS INSERT A NEW ROW (Since forms are empty, we are adding new entries)
        $id = "PLN-" . strtoupper(substr(md5(uniqid()), 0, 8));

        $query = "INSERT INTO treatment_plans (plan_id, diagnosis_id, description, start_date, end_date, created_at) 
                  VALUES (:id, :dxid, :desc, :sdate, :edate, NOW())";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':dxid', $data['diagnosis_id']);
        $stmt->bindValue(':desc', $data['description']);
        $stmt->bindValue(':sdate', $data['start_date']);

        // Handle empty end date
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;
        $stmt->bindValue(':edate', $endDate);

        $saved = $stmt->execute();
        if (!$saved) {
            return false;
        }

        // Persist TB regimen metadata if columns are available.
        $tbColumns = [];
        $tbParams = [':id' => $id];

        if ($this->hasColumn('treatment_plans', 'tb_phase')) {
            $tbColumns[] = "tb_phase = :tb_phase";
            $tbParams[':tb_phase'] = $data['tb_phase'] ?? null;
        }
        if ($this->hasColumn('treatment_plans', 'drug_regimen')) {
            $tbColumns[] = "drug_regimen = :drug_regimen";
            $tbParams[':drug_regimen'] = $data['drug_regimen'] ?? null;
        }
        if ($this->hasColumn('treatment_plans', 'adherence_status')) {
            $tbColumns[] = "adherence_status = :adherence_status";
            $tbParams[':adherence_status'] = $data['adherence_status'] ?? null;
        }

        if (!empty($tbColumns)) {
            $update = "UPDATE treatment_plans SET " . implode(', ', $tbColumns) . " WHERE plan_id = :id";
            $updateStmt = $this->conn->prepare($update);
            $updateStmt->execute($tbParams);
        }

        if (!empty($data['next_follow_up_date'])) {
            $this->createTbFollowup($data['visit_id'], $data['next_follow_up_date'], $data['follow_up_notes'] ?? null);
        }

        return true;
    }

    public function createTbFollowup($visit_id, $follow_up_date, $notes = null)
    {
        if (!$this->hasTable('tb_followups')) {
            return false;
        }

        $followupId = "FUP-" . strtoupper(substr(md5(uniqid()), 0, 8));
        $query = "INSERT INTO tb_followups (followup_id, visit_id, follow_up_date, adherence_note, created_by) VALUES (:id, :visit_id, :follow_up_date, :note, :created_by)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $followupId,
            ':visit_id' => $visit_id,
            ':follow_up_date' => $follow_up_date,
            ':note' => $notes,
            ':created_by' => $_SESSION['user_id']
        ]);
    }

    // NEW: Fetch Treatment History for a Patient
    public function getPatientTreatmentHistory($patient_id)
    {
        // IMPORTANT: We must JOIN diagnoses to get the text
        $query = "SELECT tp.*, d.diagnosis_details, d.diagnosis_date, d.diagnosis_id 
                  FROM treatment_plans tp
                  JOIN diagnoses d ON tp.diagnosis_id = d.diagnosis_id
                  JOIN medical_visits v ON d.visit_id = v.visit_id
                  WHERE v.patient_id = :pid 
                  ORDER BY tp.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $patient_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // NEW: Update Treatment Plan
    public function updateTreatmentPlan($plan_id, $description, $start_date, $end_date)
    {
        $query = "UPDATE treatment_plans SET description = :desc, start_date = :sdate, end_date = :edate WHERE plan_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':sdate', $start_date);
        $stmt->bindParam(':edate', $end_date);
        $stmt->bindParam(':pid', $plan_id);
        return $stmt->execute();
    }

    // NEW: Delete Treatment Plan
    public function deleteTreatmentPlan($plan_id)
    {
        $query = "DELETE FROM treatment_plans WHERE plan_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $plan_id);
        return $stmt->execute();
    }

    // 3. Get Clinical Summary for a Visit
    public function getVisitSummary($visit_id)
    {
        $data = [];
        // Get Vitals
        $v = $this->conn->prepare("SELECT * FROM vital_signs WHERE visit_id = ? ORDER BY recorded_at DESC LIMIT 1");
        $v->execute([$visit_id]);
        $data['vitals'] = $v->fetch(PDO::FETCH_ASSOC);

        // Get LATEST Diagnosis (for display in the form - but now we keep history)
        $d = $this->conn->prepare("SELECT * FROM diagnoses WHERE visit_id = ? ORDER BY diagnosis_date DESC, created_at DESC LIMIT 1");
        $d->execute([$visit_id]);
        $data['diagnosis'] = $d->fetch(PDO::FETCH_ASSOC);

        // Fetch Treatment Plan (linked to the LATEST diagnosis)
        if ($data['diagnosis']) {
            $t = $this->conn->prepare("SELECT * FROM treatment_plans WHERE diagnosis_id = ? ORDER BY created_at DESC LIMIT 1");
            $t->execute([$data['diagnosis']['diagnosis_id']]);
            $data['treatment'] = $t->fetch(PDO::FETCH_ASSOC);
        } else {
            $data['treatment'] = null;
        }

        return $data;
    }

    // 4. prescription and discharge
    public function addPrescription($visit_id, $med, $dose, $user_id, $tbPhase = null, $followupDate = null)
    {
        if ($visit_id === "NO_ACTIVE_VISIT" || empty($visit_id)) {
            return false;
        }

        $id = "RX-" . strtoupper(substr(md5(uniqid()), 0, 6)); // This is the ID we need
        $stmt = $this->conn->prepare("INSERT INTO prescriptions (prescription_id, visit_id, medication_name, dosage, prescribed_by) VALUES (?, ?, ?, ?, ?)");

        try {
            if ($stmt->execute([$id, $visit_id, $med, $dose, $user_id])) {
                $tbColumns = [];
                $tbParams = [':id' => $id];
                if ($this->hasColumn('prescriptions', 'tb_phase')) {
                    $tbColumns[] = "tb_phase = :tb_phase";
                    $tbParams[':tb_phase'] = $tbPhase;
                }
                if ($this->hasColumn('prescriptions', 'next_followup_date')) {
                    $tbColumns[] = "next_followup_date = :next_followup_date";
                    $tbParams[':next_followup_date'] = $followupDate;
                }
                if (!empty($tbColumns)) {
                    $update = "UPDATE prescriptions SET " . implode(', ', $tbColumns) . " WHERE prescription_id = :id";
                    $updateStmt = $this->conn->prepare($update);
                    $updateStmt->execute($tbParams);
                }

                return $id; // MODIFIED: Return the ID string for the Assignment Engine
            }
            return false;
        } catch (PDOException $e) {
            error_log("Prescription Error: " . $e->getMessage());
            return false;
        }
    }

   
}