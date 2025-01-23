<?php
require_once __DIR__ . '/../src/bootstrap.php';
//use Database\Connection;

try {
    //디비 instance connection 처리
    //$db = Connection::getInstance()->getConnection();

    // 현재 파일명을 가져와서 기본 뷰 렌더링 명칭을 지정
    $filename = basename($_SERVER['PHP_SELF'], '.php');
    // 뷰 렌더링
    view($filename, [
        'title' => '홈페이지',
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}