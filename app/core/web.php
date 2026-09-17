<?php 
session_start();

if(isset($_GET["url"])) {
    $url = explode("/", trim($_GET["url"], "/"));
} else {
    $url = ["dashboard"];
}

// Routing Table
if ($url[0] == "dashboard" || $url[0] == "") {
    require_once "../app/controllers/Dashboard.php";
    $controller = new Dashboard();
    $controller->index();
} 
else if ($url[0] == "api" && isset($url[1])) {
    require_once "../app/controllers/Dashboard.php";
    $controller = new Dashboard();
    
    switch ($url[1]) {
        case 'hives':
            $controller->getHives();
            break;
        case 'hives_all':
            $controller->getAllHives();
            break;
        case 'hive_create':
            $controller->createHive();
            break;
        case 'hive_update':
            $controller->updateHive();
            break;
        case 'hive_delete':
            $controller->deleteHive();
            break;
        case 'hive_restore':
            $controller->restoreHive();
            break;
        case 'hive_permanent_delete':
            $controller->permanentDeleteHive();
            break;
        case 'readings':
            $controller->getReadings();
            break;
        case 'current':
            $controller->getCurrent();
            break;
        case 'history':
            $controller->getHistory();
            break;
        case 'daily_stats':
            $controller->getDailyStats();
            break;
        case 'calendar_dots':
            $controller->getCalendarDots();
            break;
        case 'note_by_date':
            $controller->getNoteByDate();
            break;
        case 'note_save':
            $controller->saveNote();
            break;
        case 'note_delete':
            $controller->deleteNote();
            break;
        case 'announcements':
            $controller->getAnnouncements();
            break;
        case 'announcements_public':
            $controller->getAnnouncements();
            break;
        case 'announcement_create':
            $controller->createAnnouncement();
            break;
        case 'announcement_update':
            $controller->updateAnnouncement();
            break;
        case 'announcement_delete':
            $controller->deleteAnnouncement();
            break;
        case 'announcement_toggle':
            $controller->toggleAnnouncement();
            break;
        default:
            $controller->jsonResponse(['success' => false, 'message' => 'Unknown endpoint']);
    }
}
else if ($url[0] == "ingest") {
    require_once "../app/controllers/Ingest.php";
    $controller = new Ingest();
    $controller->index();
}
else {
    require_once "../app/controllers/_404.php";
    $controller = new _404();
    $controller->index();
}
?>