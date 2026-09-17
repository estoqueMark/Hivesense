<?php
require_once __DIR__ . "/Base_Controller.php";

class Hive_Controller extends Base_Controller {
    public $hiveModel;
    public $measurementModel;
    public $calendarNoteModel;
    public $announcementModel;
    public $userModel;
    public $alertModel;

    public function __construct(){
        parent::__construct();

        require_once __DIR__ . "/../models/Base_Model.php";
        require_once __DIR__ . "/../models/Hive_Model.php";
        require_once __DIR__ . "/../models/Measurement_Model.php";
        require_once __DIR__ . "/../models/CalendarNote_Model.php";
        require_once __DIR__ . "/../models/Announcement_Model.php";
        require_once __DIR__ . "/../models/User_Model.php";
        require_once __DIR__ . "/../models/Alert_Model.php";

        $this->hiveModel         = new Hive_Model();
        $this->measurementModel  = new Measurement_Model();
        $this->calendarNoteModel = new CalendarNote_Model();
        $this->announcementModel = new Announcement_Model();
        $this->userModel         = new User_Model();
        $this->alertModel        = new Alert_Model();
    }
}
?>