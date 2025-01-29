<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;
use Payments\Toss;

try {
    $db = Connection::getInstance()->getConnection();
    $toss = Toss::getInstance();
    $limit = 5; // 페이지당 표시할 항목 수
    $statusMap = [
        'pending' => ['label' => '주문접수', 'class' => 'bg-yellow-100 text-yellow-800'],
        'paid' => ['label' => '결제완료', 'class' => 'bg-blue-100 text-blue-800'],
        'cancelled' => ['label' => '주문취소', 'class' => 'bg-red-100 text-red-800'],
        'completed' => ['label' => '배송완료', 'class' => 'bg-green-100 text-green-800']
    ];
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
        SELECT p.*, o.* 
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
        'statusMap'=>$statusMap,
        'payments' => $payments,
        'toss'=>$toss
    ], '/');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
