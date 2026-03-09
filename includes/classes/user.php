<?php
class User
{
    private $conn;
    private $table = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // --- IMITATING PATIENT LOGIC: Count Search Results ---
    public function countSearch($term = "")
    {
        $query = "SELECT COUNT(*) FROM " . $this->table;
        if (!empty($term)) {
            // searching Name, Email, or User ID
            $query .= " WHERE full_name LIKE :term OR email LIKE :term OR user_id LIKE :term";
        }
        $stmt = $this->conn->prepare($query);
        if (!empty($term)) {
            $search_term = "%$term%";
            $stmt->bindParam(':term', $search_term);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function searchPaginated($term = "", $limit = 10, $offset = 0)
    {
        $query = "SELECT * FROM " . $this->table;
        if (!empty($term)) {
            $query .= " WHERE full_name LIKE :term OR email LIKE :term OR user_id LIKE :term";
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
    public function searchStaff($term)
    {
        $sql = "SELECT full_name, email, role, user_id, status FROM users 
            WHERE full_name LIKE :term OR email LIKE :term OR user_id LIKE :term 
            LIMIT 10";
        $stmt = $this->conn->prepare($sql);
        $term = "%$term%";
        $stmt->bindParam(':term', $term);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Add this to the User class in includes/classes/User.php

    public function getActiveLoadCount($id, $role)
    {
        switch ($role) {
            case 'Doctor':
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM medical_visits WHERE assigned_doctor_id = ? AND status = 'active'");
                break;
            case 'Nurse':
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM medical_visits WHERE assigned_nurse_id = ? AND status = 'active'");
                break;
            case 'Lab Technician':
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM lab_requests WHERE assigned_tech_id = ? AND status = 'pending'");
                break;
            case 'Radiologist':
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM radiology_requests WHERE assigned_rad_id = ? AND status = 'pending'");
                break;
                
            default:
                return 0;
        }
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }
    // Update toggleStatus in User class
    public function toggleStatusWithCare($id, $current_status, $reason, $assignmentEngine)
    {
        // 1. Determine new status
        $new_status = ($current_status == 'active') ? 'disabled' : 'active';

        // 2. If we are disabling a staff member, migrate their patients FIRST
        if ($new_status == 'disabled') {
            $user = $this->getOne($id);
            // This moves all "Active" patients to the least-loaded available peer
            $assignmentEngine->triggerFullReassignment($id, $user['role']);
        }

        // 3. Update the users table (including the new status_reason column)
        $query = "UPDATE users SET status = :status, status_reason = :reason WHERE user_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // 4. Log to status_logs for the permanent Audit Trail
        $logQuery = "INSERT INTO status_logs (user_id, admin_id, old_status, new_status, reason) 
                 VALUES (:uid, :aid, :old, :new, :reason)";
        $logStmt = $this->conn->prepare($logQuery);
        $logStmt->bindParam(':uid', $id);
        $logStmt->bindParam(':aid', $_SESSION['user_id']); // Admin performing the change
        $logStmt->bindParam(':old', $current_status);
        $logStmt->bindParam(':new', $new_status);
        $logStmt->bindParam(':reason', $reason);

        return $logStmt->execute();
    }

    // --- PAGINATION & COUNTING ---
    // --- UPDATED PAGINATION & SEARCH LOGIC ---

    public function countAll($searchTerm = "")
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        if (!empty($searchTerm)) {
            $query .= " WHERE full_name LIKE :q OR email LIKE :q OR user_id LIKE :q";
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
            $query .= " WHERE full_name LIKE :q OR email LIKE :q OR user_id LIKE :q";
        }
        $query .= " ORDER BY user_id DESC LIMIT :limit OFFSET :offset";

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
    public function changePassword($email, $current_password, $new_password)
    {
        // Validate inputs
        if (empty($email) || empty($current_password) || empty($new_password)) {
            return "All fields are required";
        }

        // Validate new password length
        if (strlen($new_password) < 8) {
            return "Password must be at least 8 characters long";
        }

        // Get user by email including password hash
        $query = "SELECT user_id, password_hash FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            return "Email not found in system";
        }

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify current password
        if (!password_verify($current_password, $user['password_hash'])) {
            return "Current password is incorrect";
        }

        // Check if new password is same as current
        if (password_verify($new_password, $user['password_hash'])) {
            return "New password must be different from current password";
        }

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in database
        $update = "UPDATE " . $this->table . " SET password_hash = :hash WHERE email = :email";
        $upStmt = $this->conn->prepare($update);
        $upStmt->bindParam(':hash', $hashed_password);
        $upStmt->bindParam(':email', $email);

        if ($upStmt->execute()) {
            return true;
        }

        return "Failed to update password. Please try again.";
    }

    // --- AUTO GENERATE ID LOGIC ---
    private function generateNextUserId()
    {
        $query = "SELECT user_id FROM " . $this->table . " ORDER BY user_id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $last_id = $row['user_id']; // Format: STF-001
            $id_number = intval(substr($last_id, 4)); // Get the "001" part
            $next_number = $id_number + 1;
            return "STF-" . str_pad($next_number, 3, "0", STR_PAD_LEFT);
        }
        return "STF-001"; // Starting ID
    }

    // --- BULK DELETE ---
    public function bulkDelete($ids)
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "DELETE FROM " . $this->table . " WHERE user_id IN ($placeholders) AND user_id != ?";
        $stmt = $this->conn->prepare($query);
        // Execute with IDs + current session ID to prevent self-deletion
        return $stmt->execute(array_merge($ids, [$_SESSION['user_id']]));
    }


    // Login using EMAIL
    public function login($email, $password)
    {
        // 1. Prepare query to find user by email
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // 2. Check if user exists
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Check if account is disabled (User Experience)
            if ($row['status'] == 'disabled') {
                return "account_disabled";
            }

            // 4. Verify password against the hash in database
            if (password_verify($password, $row['password_hash'])) {

                // Ensure session is active before setting variables
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // 5. Set essential Session variables for the UI
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['full_name'] = $row['full_name']; // FIXED: Restored so Topbar/Sidebar works
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];

                return true;
            }
        }

        // 6. Generic failure for incorrect email or password
        return false;
    }

 
    // Inside User class in includes/classes/User.php

    public function create($data)
    {
        try {
            $new_id = $this->generateNextUserId();
            $query = "INSERT INTO users 
          (user_id, email, password_hash, full_name, role, status, created_at) 
          VALUES (:id, :email, :password, :name, :role, 'active', NOW())";

            $stmt = $this->conn->prepare($query);
            // Default password if not provided (for imports)
            $pass = isset($data['password']) ? $data['password'] : 'Mattu@123';
            $hashed = password_hash($pass, PASSWORD_DEFAULT);

            $stmt->bindParam(':id', $new_id);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashed);
            $stmt->bindParam(':name', $data['full_name']);
            $stmt->bindParam(':role', $data['role']);

            return $stmt->execute();
        } catch (PDOException $e) {
            // If it's a duplicate entry (Error 23000), return false instead of crashing
            return false;
        }
    }
    public function getAll()
    {
        return $this->conn->query("SELECT * FROM users ORDER BY created_at DESC");
    }

    public function getOne($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRole($id, $role)
    {
        $stmt = $this->conn->prepare("UPDATE users SET role = :role WHERE user_id = :id");
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function toggleStatus($id, $current)
    {
        $new = ($current == 'active') ? 'disabled' : 'active';
        $stmt = $this->conn->prepare("UPDATE users SET status = :s WHERE user_id = :id");
        $stmt->bindParam(':s', $new);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function delete($id)
    {
        if ($id == $_SESSION['user_id'])
            return false;
        $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}