<?php
require_once __DIR__ . "/config.php";

class Base_Controller {
    public $model;
    public $url;

    public function __construct(){
        $this->url = ROOT;
    }

    function render($page){
        if(file_exists($page)) {
            include($page);
        } else {
            $this->view('404');
        }
    }

    public function view($view, $data = []) {
        if (!empty($data)) {
            extract($data);
        }

        $viewsDir = defined('VIEWS_PATH')
            ? VIEWS_PATH
            : __DIR__ . '/../../app/views';

        $viewFile = $viewsDir . "/" . $view . ".view.php";
        if (file_exists($viewFile)) {
            include $viewFile;
            return;
        }

        $viewFile = $viewsDir . "/" . $view . ".php";
        if (file_exists($viewFile)) {
            include $viewFile;
            return;
        }

        die("View '{$view}' not found");
    }

public function redirect($path) {
        header("Location: " . $this->url . $path);
        exit();
    }
    
    protected function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']);
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            if ($this->isApiRequest())
                $this->jsonResponse(['success' => false, 'message' => 'Unauthenticated.']);
            $this->redirect('/login');
        }
    }

    public function requireAdmin(): void {
        $this->requireLogin();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            if ($this->isApiRequest())
                $this->jsonResponse(['success' => false, 'message' => 'Forbidden.']);
            $this->redirect('/dashboard');
        }
    }

    public function requireApiarist(): void {
        $this->requireLogin();
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'apiarist') {
            if ($this->isApiRequest())
                $this->jsonResponse(['success' => false, 'message' => 'Forbidden. Apiarist or admin access required.']);
            $this->redirect('/dashboard');
        }
    }

    protected function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
            || ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json';
    }

    public function verifyCsrfHeader(): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
        }
    }
}
?>
