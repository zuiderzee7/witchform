<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    // 상품 데이터 조회
    $stmt = $db->prepare("
        SELECT p.*, c.name as company_name 
        FROM products p 
        LEFT JOIN companies c ON p.company_id = c.id 
        ORDER BY p.created_dt DESC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo
    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;

    view($viewPath, [
        'title' => '관리자 상품 관리',
        'dir'=> '/' . $dir,
        'products' => $products
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
