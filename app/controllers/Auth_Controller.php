<?php
require_once __DIR__ . "/../core/Base_Controller.php";
require_once __DIR__ . "/../models/Base_Model.php";
require_once __DIR__ . "/../models/User_Model.php";

class Auth_Controller extends Base_Controller {

    private User_Model $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User_Model();
    }

    // ══════════════════════════════════════════════════════════
    //  PUBLIC PAGES
    // ══════════════════════════════════════════════════════════

    /** GET /login */
    public function loginPage(): void {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        $error   = $_SESSION['login_error'] ?? null;
        $success = $_SESSION['login_success'] ?? null;
        unset($_SESSION['login_error'], $_SESSION['login_success']);
        $this->view('login', ['error' => $error, 'success' => $success]);
    }

    /** POST /login/submit */
    public function loginSubmit(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }

        $login    = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            $_SESSION['login_error'] = 'Please enter your username and password.';
            $this->redirect('/login');
        }

        // Brute-force throttle
        $attempts    = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_attempt_time'] ?? 0;
        if (time() - $lastAttempt > 900) $attempts = 0;

        if ($attempts >= 5) {
            $wait = 900 - (time() - $lastAttempt);
            $_SESSION['login_error'] = "Too many failed attempts. Please wait " . ceil($wait / 60) . " minute(s).";
            $this->redirect('/login');
        }

        $user = $this->userModel->attemptLogin($login, $password);

        if (!$user) {
            $_SESSION['login_attempts']    = $attempts + 1;
            $_SESSION['last_attempt_time'] = time();
            $_SESSION['login_error']       = 'Invalid username or password.';
            $this->redirect('/login');
        }

        session_regenerate_id(true);
        unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);

        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in'] = true;

        $this->redirect('/dashboard');
    }

    /** GET /logout */
    public function logout(): void {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $this->redirect('/login');
    }

    // ══════════════════════════════════════════════════════════
    //  REGISTER
    // ══════════════════════════════════════════════════════════

    /** GET /register */
    public function registerPage(): void {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        $error  = $_SESSION['register_error'] ?? null;
        $old    = $_SESSION['register_old']   ?? [];
        unset($_SESSION['register_error'], $_SESSION['register_old']);
        $this->view('register', ['error' => $error, 'old' => $old]);
    }

    /** POST /register/submit */
    public function registerSubmit(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
        }

        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password']        ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        // Save input so we can repopulate the form on error
        $_SESSION['register_old'] = [
            'username'  => $username,
            'email'     => $email,
            'full_name' => $fullName,
        ];

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['register_error'] = 'Username, email and password are required.';
            $this->redirect('/register');
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $_SESSION['register_error'] = 'Username must be 3–30 characters (letters, numbers, underscores only).';
            $this->redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_error'] = 'Please enter a valid email address.';
            $this->redirect('/register');
        }
        if (strlen($password) < 8) {
            $_SESSION['register_error'] = 'Password must be at least 8 characters.';
            $this->redirect('/register');
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $_SESSION['register_error'] = 'Password must contain both letters and numbers.';
            $this->redirect('/register');
        }
        if ($password !== $confirm) {
            $_SESSION['register_error'] = 'Passwords do not match.';
            $this->redirect('/register');
        }
        if ($this->userModel->usernameExists($username)) {
            $_SESSION['register_error'] = 'That username is already taken.';
            $this->redirect('/register');
        }
        if ($this->userModel->emailExists($email)) {
            $_SESSION['register_error'] = 'An account with that email already exists.';
            $this->redirect('/register');
        }

        // All good — create account as viewer (admin can promote later)
        try {
            $this->userModel->createUser($username, $email, $password, $fullName, 'viewer');
            unset($_SESSION['register_old']);
            $_SESSION['login_success'] = 'Account created! You can now sign in.';
            $this->redirect('/login');
        } catch (Exception $e) {
            $_SESSION['register_error'] = 'Something went wrong. Please try again.';
            $this->redirect('/register');
        }
    }

    // ══════════════════════════════════════════════════════════
    //  USER MANAGEMENT API  (admin only)
    // ══════════════════════════════════════════════════════════

    public function getUsers(): void {
        $this->requireAdmin();
        try {
            $this->jsonResponse(['success' => true, 'data' => $this->userModel->getAllUsers()]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function createUser(): void {
        $this->requireAdmin();
        $data     = $this->getJsonBody();
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email']    ?? '');
        $password = $data['password']       ?? '';
        $fullName = trim($data['full_name'] ?? '');
        $role     = in_array($data['role'] ?? '', ['admin','apiarist','viewer']) ? $data['role'] : 'viewer';

        if (empty($username) || empty($email) || empty($password))
            $this->jsonResponse(['success' => false, 'message' => 'Username, email and password are required.']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $this->jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        if (strlen($password) < 8)
            $this->jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        if ($this->userModel->usernameExists($username))
            $this->jsonResponse(['success' => false, 'message' => 'Username already taken.']);
        if ($this->userModel->emailExists($email))
            $this->jsonResponse(['success' => false, 'message' => 'Email already registered.']);

        try {
            $id = $this->userModel->createUser($username, $email, $password, $fullName, $role);
            $this->jsonResponse(['success' => true, 'user_id' => $id]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateUser(): void {
        $this->requireAdmin();
        $data     = $this->getJsonBody();
        $id       = (int)($data['user_id'] ?? 0);
        $username = trim($data['username']  ?? '');
        $email    = trim($data['email']     ?? '');
        $fullName = trim($data['full_name'] ?? '');
        $role     = in_array($data['role'] ?? '', ['admin','apiarist','viewer']) ? $data['role'] : 'viewer';

        if (!$id || empty($username) || empty($email))
            $this->jsonResponse(['success' => false, 'message' => 'Invalid data.']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $this->jsonResponse(['success' => false, 'message' => 'Invalid email address.']);

        // Protect sole admin
        if ($role !== 'admin') {
            $current = $this->userModel->getUserById($id);
            if ($current && $current['role'] === 'admin') {
                $remaining = array_filter($this->userModel->getAllUsers(),
                    fn($u) => $u['role'] === 'admin' && (int)$u['user_id'] !== $id);
                if (empty($remaining))
                    $this->jsonResponse(['success' => false, 'message' => 'Cannot demote the only admin account.']);
            }
        }
        if ($this->userModel->usernameExists($username, $id))
            $this->jsonResponse(['success' => false, 'message' => 'Username already taken.']);
        if ($this->userModel->emailExists($email, $id))
            $this->jsonResponse(['success' => false, 'message' => 'Email already registered.']);

        try {
            $this->jsonResponse(['success' => $this->userModel->updateUser($id, $username, $email, $fullName, $role)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function resetPassword(): void {
        $this->requireAdmin();
        $data     = $this->getJsonBody();
        $id       = (int)($data['user_id']  ?? 0);
        $password = $data['password'] ?? '';

        if (!$id || strlen($password) < 8)
            $this->jsonResponse(['success' => false, 'message' => 'User ID and a password of at least 8 characters are required.']);
        try {
            $this->jsonResponse(['success' => $this->userModel->updatePassword($id, $password)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function changeOwnPassword(): void {
        $this->requireLogin();
        $data        = $this->getJsonBody();
        $current     = $data['current_password'] ?? '';
        $newPassword = $data['new_password']      ?? '';

        if (empty($current) || strlen($newPassword) < 8)
            $this->jsonResponse(['success' => false, 'message' => 'Current password and a new password of at least 8 characters are required.']);

        $user = $this->userModel->findByUsernameOrEmail($_SESSION['username']);
        if (!$user || !password_verify($current, $user['password_hash']))
            $this->jsonResponse(['success' => false, 'message' => 'Current password is incorrect.']);

        try {
            $this->jsonResponse(['success' => $this->userModel->updatePassword((int)$_SESSION['user_id'], $newPassword)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function toggleUserActive(): void {
        $this->requireAdmin();
        $data = $this->getJsonBody();
        $id   = (int)($data['user_id'] ?? 0);

        if (!$id)
            $this->jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
        if ($id === (int)$_SESSION['user_id'])
            $this->jsonResponse(['success' => false, 'message' => 'You cannot deactivate your own account.']);
        try {
            $this->jsonResponse(['success' => $this->userModel->toggleActive($id)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteUser(): void {
        $this->requireAdmin();
        $data = $this->getJsonBody();
        $id   = (int)($data['user_id'] ?? 0);

        if (!$id)
            $this->jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
        if ($id === (int)$_SESSION['user_id'])
            $this->jsonResponse(['success' => false, 'message' => 'You cannot delete your own account.']);
        try {
            $this->jsonResponse(['success' => $this->userModel->deleteUser($id)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

private function getJsonBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
?>
