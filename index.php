<?php
/**
 * HiveSense — Front Controller
 */

session_start();

// ── Paths ─────────────────────────────────────────────────────
define('BASE_PATH',        __DIR__);           // HiveSense_Website/
define('APP_PATH',         BASE_PATH . '/app');
define('CORE_PATH',        APP_PATH  . '/core');
define('CONTROLLERS_PATH', APP_PATH  . '/controllers');
define('MODELS_PATH',      APP_PATH  . '/models');
define('VIEWS_PATH',       APP_PATH  . '/views');
define('HELPERS_PATH',     APP_PATH  . '/helpers');

// ── Bootstrap ─────────────────────────────────────────────────
require_once CORE_PATH . '/config.php';
require_once CORE_PATH . '/Base_Controller.php';
require_once CORE_PATH . '/Hive_Controller.php';

if (file_exists(HELPERS_PATH . '/functions.php')) {
    require_once HELPERS_PATH . '/functions.php';
}

// ── CSRF token (once per session) ─────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── URL segments ──────────────────────────────────────────────
$url      = isset($_GET['url']) ? explode('/', trim($_GET['url'], '/')) : [''];
$seg0     = $url[0] ?? '';
$seg1     = $url[1] ?? '';

// ── Auth helpers ──────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['logged_in']);
}

function guardLogin(): void {
    if (!isLoggedIn()) {
        denyAccess();
    }
    if (!sessionUserStillActive()) {
        session_unset();
        session_destroy();
        denyAccess();
    }
}

function sessionUserStillActive(): bool {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) return false;

    require_once MODELS_PATH . '/Base_Model.php';
    $db = new Base_Model();
    $stmt = $db->connection->prepare('SELECT is_active FROM hs_users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && (int)$row['is_active'] === 1;
}

function denyAccess(): void {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Your session is no longer valid. Please sign in again.']);
        exit();
    }
    header('Location: ' . ROOT . '/login');
    exit();
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $_SESSION['login_error'] = 'Invalid request. Please try again.';
        header('Location: ' . ROOT . '/login');
        exit();
    }
}

// ════════════════════════════════════════════════════════════
//  ROUTING
// ════════════════════════════════════════════════════════════

// -- PUBLIC ANNOUNCEMENTS (no auth) ------------------------------------------
if ($seg0 === 'api' && $seg1 === 'announcements_public') {
    require_once MODELS_PATH . '/Base_Model.php';
    require_once MODELS_PATH . '/Announcement_Model.php';
    $model = new Announcement_Model();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $model->getActive()]);
    exit();
}

// ── HOME (public landing page) ────────────────────────────────
if ($seg0 === '' || $seg0 === 'home') {
    // Already logged in? Skip landing page, go straight to dashboard
    if (isLoggedIn()) {
        header('Location: ' . ROOT . '/dashboard');
        exit();
    }
    (new Base_Controller())->view('home');
}

// ── LOGIN ─────────────────────────────────────────────────────
elseif ($seg0 === 'login') {
    require_once CONTROLLERS_PATH . '/Auth_Controller.php';
    $auth = new Auth_Controller();

    if ($seg1 === 'submit') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') verifyCsrf();
        $auth->loginSubmit();
    } else {
        $auth->loginPage();
    }
}

// ── LOGOUT ────────────────────────────────────────────────────
elseif ($seg0 === 'logout') {
    require_once CONTROLLERS_PATH . '/Auth_Controller.php';
    (new Auth_Controller())->logout();
}

// ── TERMS & CONDITIONS (public, no auth) ────────────────────
elseif ($seg0 === 'terms') {
    (new Base_Controller())->view('terms');
}

// ── REGISTER ─────────────────────────────────────────────────
elseif ($seg0 === 'register') {
    // Already logged in? No need to register
    if (isLoggedIn()) {
        header('Location: ' . ROOT . '/dashboard');
        exit();
    }
    require_once CONTROLLERS_PATH . '/Auth_Controller.php';
    $auth = new Auth_Controller();

    if ($seg1 === 'submit') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF check for register form too
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                $_SESSION['register_error'] = 'Invalid request. Please try again.';
                header('Location: ' . ROOT . '/register');
                exit();
            }
        }
        $auth->registerSubmit();
    } else {
        $auth->registerPage();
    }
}

// ── DASHBOARD (protected) ─────────────────────────────────────
elseif ($seg0 === 'dashboard') {
    guardLogin();
    require_once CONTROLLERS_PATH . '/Dashboard.php';
    (new Dashboard())->index();
}

// ── API (all protected) ───────────────────────────────────────
elseif ($seg0 === 'api' && $seg1 !== '') {
    guardLogin();
    require_once CONTROLLERS_PATH . '/Dashboard.php';
    require_once CONTROLLERS_PATH . '/Auth_Controller.php';
    $dash = new Dashboard();
    $auth = new Auth_Controller();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dash->verifyCsrfHeader();
    }

    switch ($seg1) {
        case 'readings':              $dash->getReadings();          break;
        // Sensor data
        case 'current':               $dash->getCurrent();           break;
        case 'history':               $dash->getHistory();           break;
        case 'daily_stats':           $dash->getDailyStats();        break;
        // Hives
        case 'hives':                 $dash->getHives();             break;
        case 'hives_all':             $dash->getAllHives();           break;
        case 'hive_create':           $dash->createHive();           break;
        case 'hive_update':           $dash->updateHive();           break;
        case 'hive_delete':           $dash->deleteHive();           break;
        case 'hive_restore':          $dash->restoreHive();          break;
        case 'hive_permanent_delete': $dash->permanentDeleteHive();  break;
        // Calendar / notes
        case 'calendar_dots':         $dash->getCalendarDots();      break;
        case 'note_by_date':          $dash->getNoteByDate();        break;
        case 'note_export':           $dash->exportNotesCsv();       break;
        case 'hive_note_dates':       $dash->getHiveNoteDates();     break;
		case 'note_by_id':            $dash->getNoteById();          break;    
        case 'note_save':             $dash->saveNote();             break;
        case 'note_delete':           $dash->deleteNote();           break;
        // User management
        case 'users':                 $auth->getUsers();             break;
        case 'user_create':           $auth->createUser();           break;
        case 'user_update':           $auth->updateUser();           break;
        case 'user_reset_password':   $auth->resetPassword();        break;
        case 'user_change_password':  $auth->changeOwnPassword();    break;
        case 'user_toggle_active':    $auth->toggleUserActive();     break;
        case 'user_delete':           $auth->deleteUser();           break;
        // Announcements
        case 'announcements':             $dash->getAnnouncements();     break;
        case 'announcement_create':       $dash->createAnnouncement();   break;
        case 'announcement_update':       $dash->updateAnnouncement();   break;
        case 'announcement_delete':       $dash->deleteAnnouncement();   break;
        case 'announcement_toggle':       $dash->toggleAnnouncement();   break;
        case 'alerts':                    $dash->getAlerts();            break;
        case 'alert_history':             $dash->getAlertHistory();      break;
        case 'alert_acknowledge':         $dash->acknowledgeAlert();     break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unknown API endpoint.']);
            exit();
    }
}

// ── INGEST (API key auth — no session needed) ─────────────────
elseif ($seg0 === 'ingest') {
    require_once CONTROLLERS_PATH . '/Ingest.php';
    (new Ingest())->index();
}

// ── 404 ───────────────────────────────────────────────────────
else {
    require_once CONTROLLERS_PATH . '/_404.php';
    (new _404())->index();
}
?>
