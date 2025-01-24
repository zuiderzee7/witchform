<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            throw new Exception('지원하지 않는 메소드입니다.');
    }

} catch (Exception $e) {
    handleError($e);
}

function handleGet($db) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('업체 ID가 필요합니다.');
    }

    $stmt = $db->prepare("SELECT * FROM companies WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        throw new Exception('존재하지 않는 업체입니다.');
    }

    header('Content-Type: application/json');
    echo json_encode($company);
    exit;
}

function handlePost($db) {
    validateFields(['name', 'email', 'contact', 'address']);

    $sql = "INSERT INTO companies (name, email, contact, postal_code, address) 
            VALUES (:name, :email, :contact, :postal_code, :address)";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'contact' => $_POST['contact'],
        'postal_code' => $_POST['postal_code'] ?? null,
        'address' => $_POST['address']
    ]);

    if ($result) {
        header('Location: /admin/company?success=create');
        exit;
    }
}

function handlePut($db) {
    parse_str(file_get_contents("php://input"), $_PUT);

    if (!isset($_PUT['id'])) {
        throw new Exception('업체 ID가 필요합니다.');
    }

    validateFields(['name', 'email', 'contact', 'address'], $_PUT);

    $sql = "UPDATE companies 
            SET name = :name,
                email = :email,
                contact = :contact,
                postal_code = :postal_code,
                address = :address
            WHERE id = :id";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        'id' => $_PUT['id'],
        'name' => $_PUT['name'],
        'email' => $_PUT['email'],
        'contact' => $_PUT['contact'],
        'postal_code' => $_PUT['postal_code'] ?? null,
        'address' => $_PUT['address']
    ]);

    if ($result) {
        header('Location: /admin/company?success=update');
        exit;
    }
}

function handleDelete($db) {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception('삭제할 업체 ID가 필요합니다.');
    }

    // 업체 존재 여부 확인
    $checkStmt = $db->prepare("SELECT id FROM companies WHERE id = ?");
    $checkStmt->execute([$id]);
    if (!$checkStmt->fetch()) {
        throw new Exception('존재하지 않는 업체입니다.');
    }

    $sql = "DELETE FROM companies WHERE id = :id";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute(['id' => $id]);

    if ($result) {
        header('Location: /admin/company?success=delete');
        exit;
    }

    throw new Exception('업체 삭제에 실패했습니다.');
}

function validateFields($required_fields, $data = null) {
    $data = $data ?? $_POST;
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "{$field}은(는) 필수 입력항목입니다.";
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }

    return true;
}

function handleError($e) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        error_log($e->getMessage());
        header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=' . urlencode($e->getMessage()));
    }
    exit;
}

function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
