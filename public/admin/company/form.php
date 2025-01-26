<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();
    $data = null;

    // ID가 있는 경우 업체 정보 조회
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM companies WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new Exception('존재하지 않는 업체입니다.');
        }
    }

    $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $file = basename($_SERVER['SCRIPT_NAME'], '.php');
    $viewPath = $dir ? $dir . '/' . $file : $file;

    view($viewPath, [
        'title' => isset($data) ? '업체 수정' : '업체 등록',
        'dir'=> '/' . $dir,
        'data' => $data
    ], '/admin');

} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: /admin/company?error=' . urlencode($e->getMessage()));
    exit;
}
