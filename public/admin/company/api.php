<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

// 세션이 시작되지 않았다면 시작 (bootstrap.php에서 이미 시작했을 수도 있음)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = Connection::getInstance()->getConnection();

    // _method 필드를 우선순위로 하여 HTTP 메서드를 결정
    $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handleCsrf($_POST['_csrf_token'] ?? ''); // CSRF 체크
            handlePost($db);
            break;
        case 'PUT':
            // PUT 데이터 파싱
            parse_str(file_get_contents("php://input"), $_PUT);
            handleCsrf($_PUT['_csrf_token'] ?? ''); // CSRF 체크
            handlePut($db, $_PUT);
            break;
        case 'DELETE':
            handleCsrf($_POST['_csrf_token'] ?? ''); // CSRF 체크
            handleDelete($db);
            break;
        default:
            throw new Exception('지원하지 않는 메소드입니다.');
    }

} catch (Exception $e) {
    handleError($e);
}

/**
 * GET 메소드 핸들러
 */
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

/**
 * POST 메소드 핸들러 (데이터 생성)
 */
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
        header('Location: /admin/company');
        exit;
    }
}

/**
 * PUT 메소드 핸들러 (데이터 수정)
 */
function handlePut($db, $_PUT) {
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
        header('Location: /admin/company');
        exit;
    }
}

/**
 * DELETE 메소드 핸들러 (데이터 삭제)
 */
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
        header('Location: /admin/company');
        exit;
    }

    throw new Exception('업체 삭제에 실패했습니다.');
}

/**
 * 필수 필드 검증 함수
 */
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

/**
 * CSRF 토큰 검사 함수
 */
function handleCsrf($token) {
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception('CSRF 토큰이 유효하지 않거나 없습니다.');
    }
}

/**
 * 에러 핸들링 함수
 */
function handleError($e)
{
    error_log($e->getMessage());

    $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/company';

    // 이미 쿼리 파라미터가 있는지 확인
    if (strpos($referer, '?') !== false) {
        // 기존 쿼리 파라미터가 있음 -> & 로 연결
        $redirectUrl = $referer . '&error=' . urlencode($e->getMessage());
    } else {
        // 쿼리 파라미터가 없음 -> ? 로 연결
        $redirectUrl = $referer . '?error=' . urlencode($e->getMessage());
    }

    // 최종 리다이렉트
    header('Location: ' . $redirectUrl);
    exit;
}