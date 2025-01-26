<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    $limit = 5; // 페이지당 표시할 항목 수

    // 게시물 데이터 조회
    $postStmt = $db->prepare("
        SELECT p.*, c.name as company_name 
        FROM posts p 
        LEFT JOIN companies c ON p.company_id = c.id 
        ORDER BY p.created_dt DESC
        LIMIT :limit
    ");
    $postStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $postStmt->execute();
    $posts = $postStmt->fetchAll(PDO::FETCH_ASSOC);

    // 주문 데이터 조회
    $orderStmt = $db->prepare("
        SELECT o.*, c.name as company_name 
        FROM orders o 
        LEFT JOIN companies c ON o.company_id = c.id 
        ORDER BY o.created_dt DESC
        LIMIT :limit
    ");
    $orderStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $orderStmt->execute();
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    // 결제 데이터 조회
    $paymentStmt = $db->prepare("
        SELECT p.*, o.order_number 
        FROM payments p 
        LEFT JOIN orders o ON p.order_id = o.id 
        ORDER BY p.created_dt DESC
        LIMIT :limit
    ");
    $paymentStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $paymentStmt->execute();
    $payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;

    view($viewPath, [
        'title' => '메인 페이지',
        'dir'=> '/'.$dir,
        'posts' => $posts,
        'orders' => $orders,
        'payments' => $payments,
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
