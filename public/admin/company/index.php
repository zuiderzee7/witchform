<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    // 페이지네이션 설정
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // 전체 업체 수 조회
    $countStmt = $db->query("SELECT COUNT(*) FROM companies");
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 업체 데이터 조회
    $stmt = $db->prepare("
        SELECT * FROM companies 
        ORDER BY created_dt DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;

    view($viewPath, [
        'title' => '관리자 업체 관리',
        'dir'=> '/' . $dir,
        'companies' => $companies,
        'current_page' => $page,
        'total_pages' => $total_pages
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
