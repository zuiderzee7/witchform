<?php
//에러 표출 관련 설정
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
//use Database\Connection;

try {
    //디비 instance connection 처리
    //$db = Connection::getInstance()->getConnection();

    // 현재 폴더, 파일명을 가져와서 기본 뷰 렌더링 명칭을 지정
    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;
    view($viewPath, [
        'title' => '관리자',
        'dir'=> '/'.$dir
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}