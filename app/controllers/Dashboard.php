<?php
require_once __DIR__ . "/../core/Hive_Controller.php";

class Dashboard extends Hive_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $roleNotice = $this->syncSessionRole();
        error_log('DEBUG roleNotice: ' . var_export($roleNotice, true) . ' | session role: ' . ($_SESSION['role'] ?? 'none'));
        $hives = $this->hiveModel->getActiveHives();
        $this->view('dashboard', ['hives' => $hives, 'roleNotice' => $roleNotice]);
    }

    private function syncSessionRole(): ?string {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if (!$userId) return null;

        $freshUser = $this->userModel->getUserById($userId);
        if (!$freshUser) return null;

        $oldRole = $_SESSION['role'] ?? '';
        $newRole = $freshUser['role'];

        if ($oldRole !== $newRole) {
            $_SESSION['role'] = $newRole;
            return "Your account role was updated to " . ucfirst($newRole) . ". Your access has been refreshed.";
        }
        return null;
    }

     public function getReadings() {
        try {
            $hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $result = $this->measurementModel->getReadings($hours, $limit, $sensorId);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getCurrent() {
        try {
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $latest = $this->measurementModel->getLatest($sensorId);
            $minMax = $this->measurementModel->getTodayMinMax($sensorId);
            
            if (!$latest) {
                $this->jsonResponse(['success' => false, 'message' => 'No data found.']);
                return;
            }
            
            $responseData = array_merge($latest, $minMax ?? []);
            $this->jsonResponse(['success' => true, 'data' => $responseData]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getHistory() {
        try {
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $result = $this->measurementModel->getHistory($days, $sensorId);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getDailyStats() {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $result = $this->measurementModel->getDailyStats($limit, $sensorId);
            $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getAllHives() {
        try {
            $hives = $this->hiveModel->getAllHives();
            $this->jsonResponse(['success' => true, 'data' => $hives]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }
    }

    public function getHives() {
        try {
            $hives = $this->hiveModel->getActiveHives();
            $this->jsonResponse(['success' => true, 'data' => $hives]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }
    }

    public function createHive() {
        try {
            $this->requireAdmin();            
            $data = $this->getJsonBody();
            $hiveName = trim($data['hive_name'] ?? '');
            $location = trim($data['location'] ?? '');
            
            if (empty($hiveName)) {
                $this->jsonResponse(['success' => false, 'message' => 'Hive name is required']);
                return;
            }
            
            $id = $this->hiveModel->createHive($hiveName, $location);
            $this->jsonResponse(['success' => true, 'sensor_id' => $id]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateHive() {
        try {
            $this->requireAdmin();            
            $data = $this->getJsonBody();
            $id = (int)($data['sensor_id'] ?? 0);
            $hiveName = trim($data['hive_name'] ?? '');
            $location = trim($data['location'] ?? '');
            
            if (!$id || empty($hiveName)) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid data']);
                return;
            }
            
            $result = $this->hiveModel->updateHive($id, $hiveName, $location);
            $this->jsonResponse(['success' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteHive() {
        try {
            $this->requireAdmin();
            $data = $this->getJsonBody();
            $id = (int)($data['sensor_id'] ?? 0);
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid hive ID']);
                return;
            }
            
            $result = $this->hiveModel->deleteHive($id);
            $this->jsonResponse(['success' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function restoreHive() {
        try {
            $this->requireAdmin();
            $data = $this->getJsonBody();
            $id = (int)($data['sensor_id'] ?? 0);
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid hive ID']);
                return;
            }
            
            $result = $this->hiveModel->restoreHive($id);
            $this->jsonResponse(['success' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function permanentDeleteHive() {
        try {
            $this->requireAdmin();
            $data = $this->getJsonBody();
            $id = (int)($data['sensor_id'] ?? 0);
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid hive ID']);
                return;
            }
            
            $result = $this->hiveModel->permanentDeleteHive($id);
            $this->jsonResponse(['success' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getCalendarDots() {
        try {
            $this->requireApiarist();
            $month = $_GET['month'] ?? date('Y-m');
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $dates = $this->calendarNoteModel->getDatesWithNotes($month, $sensorId);
            $this->jsonResponse(['success' => true, 'data' => $dates]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => true, 'data' => []]);
        }
    }
    public function exportNotesCsv() {
        $this->requireApiarist();
        $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : 0;
        if (!$sensorId) {
            http_response_code(400);
            echo 'sensor_id is required.';
            return;
        }

        $hive     = $this->hiveModel->getHiveById($sensorId);
        $hiveName = $hive['hive_name'] ?? 'hive';
        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $hiveName);
        $filename = "hivesense_notes_{$safeName}_" . date('Ymd') . ".csv";

        $notes = $this->calendarNoteModel->getNotesForExport($sensorId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM — makes Excel render special chars correctly

        fputcsv($out, [
            'Date', 'Hive Name', 'Colony ID', 'Queen Species/Breed', 'Queen Age',
            'Comb Frames (+/-)', 'Wax Foundation (+/-)', 'Feeding',
            'Colony Strength', 'Open Brood', 'Close Brood', 'Brood Pattern Type',
            'Honey Store', 'Pollen Store', 'Temperament',
            'Treatment Date', 'Chemical/Brand Used', 'Remarks/Notes'
        ]);

        foreach ($notes as $n) {
            $feeding = ($n['feeding'] === '1' || $n['feeding'] === 1) ? 'Yes'
                     : (($n['feeding'] === '0' || $n['feeding'] === 0) ? 'No' : '');
            fputcsv($out, [
                $n['note_date'], $n['hive_name'] ?? '', $n['num_colonies'] ?? '',
                $n['queen_species'] ?? '', $n['queen_age'] ?? '',
                $n['comb_frames_change'] ?? '', $n['wax_foundation_change'] ?? '', $feeding,
                $n['colony_strength'] ?? '', $n['brood_pattern_open'] ?? '', $n['brood_pattern_close'] ?? '',
                $n['brood_pattern_type'] ?? '', $n['honey_store'] ?? '', $n['pollen_store'] ?? '',
                $n['temperament'] ?? '', $n['treatment_date'] ?? '', $n['chemical_brand'] ?? '', $n['remarks'] ?? '',
            ]);
        }

        fclose($out);
        exit();
    }
    public function getNoteByDate() {
        try {
            $this->requireApiarist();
            $date = $_GET['date'] ?? date('Y-m-d');
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $note = $this->calendarNoteModel->getNoteByDateAndHive($date, $sensorId);
            $this->jsonResponse(['success' => true, 'data' => $note]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => true, 'data' => null]);
        }
    }
    
    public function getHiveNoteDates() {
        try {
            $this->requireApiarist();
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : 0;
            if (!$sensorId) {
                $this->jsonResponse(['success' => false, 'message' => 'sensor_id is required.']);
                return;
            }
            $dates = $this->calendarNoteModel->getNoteDatesForHive($sensorId);
            $this->jsonResponse(['success' => true, 'data' => $dates]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getNoteById() {
        try {
            $this->requireApiarist();
            $noteId = isset($_GET['note_id']) ? (int)$_GET['note_id'] : 0;
            if (!$noteId) {
                $this->jsonResponse(['success' => false, 'message' => 'note_id is required.']);
                return;
            }
            $note = $this->calendarNoteModel->getNoteById($noteId);
            $this->jsonResponse(['success' => true, 'data' => $note]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function saveNote() {
        try {
            $this->requireApiarist();
            $data = $this->getJsonBody();
            $noteId = $data['note_id'] ?? null;
            
            if ($noteId) {
                $result = $this->calendarNoteModel->updateNote($noteId, $data);
            } else {
                $result = $this->calendarNoteModel->createNote($data);
                $noteId = $result;
            }
            
            $this->jsonResponse(['success' => true, 'note_id' => $noteId]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteNote() {
        try {
            $this->requireApiarist();
            $data = $this->getJsonBody();
            $noteId = (int)($data['note_id'] ?? 0);
            
            if (!$noteId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid note ID']);
                return;
            }
            
            $result = $this->calendarNoteModel->deleteNote($noteId);
            $this->jsonResponse(['success' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    // ── Announcements ─────────────────────────────────────────

    public function getAnnouncements(): void {
        try {
            $announcements = $this->announcementModel->getAll();
            $this->jsonResponse(['success' => true, 'data' => $announcements]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function createAnnouncement(): void {
        try {
            $this->requireAdmin();
            $data  = $this->getJsonBody();
            $title = trim($data['title'] ?? '');
            $body  = trim($data['body']  ?? '');
            $type  = $data['type'] ?? 'info';

            if (empty($title) || empty($body)) {
                $this->jsonResponse(['success' => false, 'message' => 'Title and body are required.']);
                return;
            }
            if (!in_array($type, ['info','warning','success','alert'])) $type = 'info';

            $id = $this->announcementModel->create(
                $title, $body, $type, (int)($_SESSION['user_id'] ?? 0)
            );
            $this->jsonResponse(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateAnnouncement(): void {
        try {
            $this->requireAdmin();
            $data     = $this->getJsonBody();
            $id       = (int)($data['id']    ?? 0);
            $title    = trim($data['title']  ?? '');
            $body     = trim($data['body']   ?? '');
            $type     = $data['type']         ?? 'info';
            $isActive = (int)($data['is_active'] ?? 1);

            if (!$id || empty($title) || empty($body)) {
                $this->jsonResponse(['success' => false, 'message' => 'ID, title and body are required.']);
                return;
            }
            if (!in_array($type, ['info','warning','success','alert'])) $type = 'info';

            $result = $this->announcementModel->update($id, $title, $body, $type, $isActive);
            $this->jsonResponse(['success' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteAnnouncement(): void {
        try {
            $this->requireAdmin();
            $data = $this->getJsonBody();
            $id   = (int)($data['id'] ?? 0);
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid ID.']);
                return;
            }
            $this->jsonResponse(['success' => $this->announcementModel->delete($id)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function toggleAnnouncement(): void {
        try {
            $this->requireAdmin();
            $data = $this->getJsonBody();
            $id   = (int)($data['id'] ?? 0);
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid ID.']);
                return;
            }
            $this->jsonResponse(['success' => $this->announcementModel->toggleActive($id)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function getJsonBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

        public function getAlerts() {
        try {
            $this->requireApiarist();
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $alerts = $this->alertModel->getActiveAlerts($sensorId);
            $this->jsonResponse(['success' => true, 'data' => $alerts]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getAlertHistory() {
        try {
            $this->requireApiarist();
            $sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
            $limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $alerts = $this->alertModel->getAlertHistory($sensorId, $limit);
            $this->jsonResponse(['success' => true, 'data' => $alerts]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function acknowledgeAlert() {
        try {
            $this->requireApiarist();
            $data = $this->getJsonBody();
            $id = (int)($data['alert_id'] ?? 0);
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid alert ID']);
                return;
            }
            $result = $this->alertModel->acknowledgeAlert($id, (int)($_SESSION['user_id'] ?? 0));
            $this->jsonResponse(['success' => $result]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>