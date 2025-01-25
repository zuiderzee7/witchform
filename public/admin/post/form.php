<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    // 수정 모드인지 확인
    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $post = null;
    $checkedProductIds = [];
    $post_delivery = null;

    if ($post_id) {
        // 게시글 가져오기
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = :id");
        $stmt->execute([
            'id' => $post_id,
        ]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            throw new Exception('존재하지 않는 게시글입니다.');
        }

        //연관 상품 정보 가져오기
        $ppStmt = $db->prepare("SELECT product_id FROM post_products WHERE post_id = :post_id");
        $ppStmt->execute(['post_id' => $post_id]);
        $checkedProductIds = $ppStmt->fetchAll(PDO::FETCH_COLUMN);

        // 배송 정보 가져오기
        $deliveryStmt = $db->prepare("SELECT * FROM post_delivery WHERE post_id = :post_id");
        $deliveryStmt->execute(['post_id' => $post_id]);
        $post_delivery = $deliveryStmt->fetch(PDO::FETCH_ASSOC);
    }

    // 업체 목록 조회
    $stmt = $db->prepare("
        SELECT id, name, email, contact, postal_code, address
        FROM companies 
        ORDER BY name ASC
    ");
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 뷰 렌더
    view('admin/post/form', [
        'title' => $post_id ? '게시글 수정' : '게시글 등록',
        'post' => $post,
        'companies' => $companies,
        'checkedProductIds' => $checkedProductIds,
        'post_delivery' => $post_delivery
    ], '/admin');
} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
