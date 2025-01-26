<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();
    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$post_id) {
        throw new Exception('게시글 ID가 필요합니다.');
    }

    // 게시글 정보 조회
    $stmt = $db->prepare("
        SELECT p.*, c.name as company_name,
               pd.pickup_cost, pd.delivery_cost, pd.free_delivery_amount
        FROM posts p 
        LEFT JOIN companies c ON p.company_id = c.id
        LEFT JOIN post_delivery pd ON p.id = pd.post_id
        WHERE p.id = :id
    ");
    $stmt->execute(['id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        throw new Exception('존재하지 않는 게시글입니다.');
    }

    // 연결된 상품 목록 조회
    $productStmt = $db->prepare("
        SELECT p.*, pi.total_inventory, pi.current_inventory
        FROM post_products pp
        JOIN products p ON pp.product_id = p.id
        LEFT JOIN product_inventories pi ON p.id = pi.product_id
        WHERE pp.post_id = :post_id
    ");
    $productStmt->execute(['post_id' => $post_id]);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

    view('post/detail', [
        'title' => '게시글 상세',
        'post' => $post,
        'products' => $products
    ], '/');

} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: /post?error=' . urlencode($e->getMessage()));
    exit;
}
