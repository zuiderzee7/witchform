<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;
use Payments\Toss;

try {
    $statusMap = [
        'pending' => ['label' => '접수', 'class' => 'bg-yellow-100 text-yellow-800'],
        'ready' => ['label' => '접수', 'class' => 'bg-yellow-100 text-yellow-800'],
        'paid' => ['label' => '완료', 'class' => 'bg-blue-100 text-blue-800'],
        'cancelled' => ['label' => '취소', 'class' => 'bg-red-100 text-red-800'],
        'completed' => ['label' => '배송완료', 'class' => 'bg-green-100 text-green-800']
    ];

    $db = Connection::getInstance()->getConnection();
    $toss = Toss::getInstance();

    // 게시물 조회 (페이지네이션 없이)
    $postQuery = "
        SELECT 
            p.id,
            p.title,
            p.content,
            p.created_dt,
            c.name as company_name,
            p.company_id
        FROM posts p
        LEFT JOIN companies c ON p.company_id = c.id
        ORDER BY p.created_dt DESC
    ";
    $postStmt = $db->query($postQuery);
    $posts = $postStmt->fetchAll(PDO::FETCH_ASSOC);

    // 주문/결제 페이지네이션 설정
    $limit = 5;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // 주문/결제 전체 수 조회
    $countQuery = "
        SELECT COUNT(DISTINCT o.id) as total 
        FROM orders o
    ";
    $totalStmt = $db->query($countQuery);
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

    // 주문 데이터 조회
    $orderQuery = "
        SELECT 
            o.id,
            o.order_number,
            o.created_dt,
            c.name as company_name,
            o.company_id,
            o.order_status,
            o.customer_name,
            o.customer_email,
            o.customer_phone,
            o.delivery_type,
            o.delivery_cost,
            o.total_amount,
            p.status as payment_status,
            p.mid as payment_mid,
            p.created_dt as payment_created_dt
        FROM orders o
        LEFT JOIN companies c ON o.company_id = c.id
        LEFT JOIN payments p ON o.id = p.order_id
        ORDER BY o.created_dt DESC
        LIMIT :limit OFFSET :offset
    ";

    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $orderStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $orderStmt->execute();
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

    // 주문 ID 추출
    $orderIds = array_column($orders, 'id');

    // 결제 데이터 조회
    $payments = [];
    if (!empty($orderIds)) {
        $paymentQuery = "
            SELECT 
                p.*,
                o.id as order_id
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            WHERE o.id IN (" . implode(',', array_fill(0, count($orderIds), '?')) . ")
            ORDER BY p.created_dt DESC
        ";
        $paymentStmt = $db->prepare($paymentQuery);
        $paymentStmt->execute($orderIds);
        while ($row = $paymentStmt->fetch(PDO::FETCH_ASSOC)) {
            $payments[$row['order_id']] = $row;
        }
    }

    // 주문 상품 정보 조회
    $orderItems = [];
    if (!empty($orderIds)) {
        $itemQuery = "
            SELECT 
                oi.*, 
                p.name as product_name, 
                oi.order_id
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id IN (" . implode(',', array_fill(0, count($orderIds), '?')) . ")
            ORDER BY oi.order_id, oi.id
        ";

        $itemStmt = $db->prepare($itemQuery);
        $itemStmt->execute($orderIds);

        while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
            $orderItems[$row['order_id']][] = $row;
        }
    }

    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;

    view($viewPath, [
        'title' => '메인 페이지',
        'dir'=> '/'.$dir,
        'posts' => $posts,
        'orders' => $orders,
        'orderItems' => $orderItems,
        'statusMap' => $statusMap,
        'payments' => $payments,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit
        ],
        'toss' => $toss
    ], '/');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
    exit;
}
