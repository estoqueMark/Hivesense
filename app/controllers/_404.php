<?php
require_once __DIR__ . "/../core/Base_Controller.php";

class _404 extends Base_Controller {
   
    public function index() {
        http_response_code(404);
        $this->view('404');
    }
}