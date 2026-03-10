-- TBMIS migration script
-- Apply this on the existing PRMIS/TBMIS database.

CREATE DATABASE IF NOT EXISTS mattu_tbmis;
USE mattu_tbmis;

-- TB-specific diagnosis metadata
ALTER TABLE diagnoses
    ADD COLUMN IF NOT EXISTS tb_classification VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS tb_treatment_status VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS mdr_tb_status ENUM('Yes','No') DEFAULT 'No',
    ADD COLUMN IF NOT EXISTS diagnosis_method VARCHAR(50) NULL;

-- TB treatment plan metadata
ALTER TABLE treatment_plans
    ADD COLUMN IF NOT EXISTS tb_phase VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS drug_regimen VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS adherence_status VARCHAR(30) NULL;

-- TB laboratory request metadata
ALTER TABLE lab_requests
    ADD COLUMN IF NOT EXISTS tb_test_category VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS specimen_type VARCHAR(50) NULL;

-- TB prescription metadata
ALTER TABLE prescriptions
    ADD COLUMN IF NOT EXISTS tb_phase VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS next_followup_date DATE NULL;

-- TB follow-up tracking table
CREATE TABLE IF NOT EXISTS tb_followups (
    followup_id VARCHAR(20) NOT NULL,
    visit_id VARCHAR(20) NOT NULL,
    follow_up_date DATE NOT NULL,
    adherence_note TEXT NULL,
    outcome_status VARCHAR(40) DEFAULT 'Scheduled',
    created_by VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (followup_id),
    KEY idx_tb_followups_visit (visit_id),
    KEY idx_tb_followups_date (follow_up_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: align data labels for TB context without deleting old data
UPDATE prescriptions
SET medication_name = CONCAT('[TB-CHECK] ', medication_name)
WHERE medication_name IS NOT NULL
  AND medication_name NOT LIKE '[TB-CHECK] %';
