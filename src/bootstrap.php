<?php
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/..');

require_once BASE_PATH . '/src/Database/Connection.php';

//에러 표출 관련 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function view($name, $data = []) {
    extract($data);
    require_once BASE_PATH . "/resources/layouts/master.php";
}
