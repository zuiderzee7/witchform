<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    // 페이지네이션 설정
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 2; // 페이지당 표시할 항목 수
    $offset = ($page - 1) * $limit;

    // 전체 상품 수 조회
    $countStmt = $db->query("SELECT COUNT(*) FROM products");
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 상품 데이터 조회 (페이지네이션 적용)
    $stmt = $db->prepare("
        SELECT p.*, c.name as company_name 
        FROM products p 
        LEFT JOIN companies c ON p.company_id = c.id 
        ORDER BY p.created_dt DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;

    view($viewPath, [
        'title' => '관리자 상품 관리',
        'dir'=> '/' . $dir,
        'products' => $products ?? [],
        'current_page' => $page,
        'total_pages' => $total_pages
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
