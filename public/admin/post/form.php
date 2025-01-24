<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    // 수정 모드인지 확인
    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $post = null;

    if ($post_id) {
        // 게시글 가져오기
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = :id AND company_id = :company_id");
        $stmt->execute([
            'id' => $post_id,
        ]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            throw new Exception('존재하지 않는 게시글입니다.');
        }
    }

    $productStmt = $db->prepare("SELECT id, name FROM products WHERE company_id = :company_id");
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

    // 이미 등록된 product_id 목록(수정 모드 시)
    $checkedProductIds = [];
    if ($post_id) {
        $ppStmt = $db->prepare("SELECT product_id FROM post_products WHERE post_id = :post_id");
        $ppStmt->execute(['post_id' => $post_id]);
        $checkedProductIds = $ppStmt->fetchAll(PDO::FETCH_COLUMN); // 1차원 배열 (product_id만)
    }

    // 뷰 렌더
    view('admin/post/form', [
        'title' => $post_id ? '게시글 수정' : '게시글 등록',
        'post' => $post,
        'products' => $products,
        'checkedProductIds' => $checkedProductIds
    ], '/admin');
} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
