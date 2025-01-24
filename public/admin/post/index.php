<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    // 페이지네이션 설정
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;

    // 전체 게시글 수 조회
    $countStmt = $db->query("SELECT COUNT(*) FROM posts");
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // 게시글 목록 조회
    $stmt = $db->prepare("
        SELECT p.*, c.name AS company_name
        FROM posts p
        LEFT JOIN companies c ON p.company_id = c.id
        ORDER BY p.created_dt DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 뷰 렌더
    view('admin/post/index', [
        'title' => '게시글 관리',
        'posts' => $posts,
        'current_page' => $page,
        'total_pages' => $total_pages
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    echo $e->getMessage();
}
