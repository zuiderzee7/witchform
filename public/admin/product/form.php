<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();
    $product = null;

    // ID가 있는 경우 상품 정보 조회
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("
            SELECT p.*, c.name as company_name 
            FROM products p 
            LEFT JOIN companies c ON p.company_id = c.id 
            WHERE p.id = :id
        ");
        $stmt->execute(['id' => $_GET['id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('존재하지 않는 상품입니다.');
        }
    }

    // 회사 목록 조회
    $companiesStmt = $db->query("SELECT id, name FROM companies ORDER BY name");
    $companies = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);

    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;

    view($viewPath, [
        'title' => isset($product) ? '관리자 상품 수정' : '관리자 상품 등록',
        'dir'=> '/' . $dir,
        'product' => $product,
        'companies' => $companies
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: /admin/product?error=' . urlencode($e->getMessage()));
    exit;
}