<?php
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/..');

require_once BASE_PATH . '/src/Database/Connection.php';

//에러 표출 관련 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF 토큰이 없으면 생성
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * 뷰 랜더링 처리 function
 * @param string $file_name 랜더링 뷰파일 명
 * @param array $data 서버에서 프론트 뷰단으로 데이터 전달 용도
 * @param string $folder 레이아웃 폴더 지정
*/
function view(string $file_name, array $data = [], string $folder = '') {
    extract($data);
    require_once BASE_PATH . "/resources/layouts".$folder."/master.php";
}
