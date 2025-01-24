<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

// 세션이 시작되지 않았다면 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = Connection::getInstance()->getConnection();

    // _method 필드를 우선적으로 사용
    $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGet($db);
            break;

        case 'POST':
            // CSRF 토큰 검증
            handleCsrf($_POST['_csrf_token'] ?? '');
            handlePost($db);
            break;

        case 'PUT':
            // PUT 요청의 데이터 파싱
            parse_str(file_get_contents("php://input"), $_PUT);
            // CSRF 토큰 검증
            handleCsrf($_PUT['_csrf_token'] ?? '');
            handlePut($db, $_PUT);
            break;

        case 'DELETE':
            // CSRF 토큰 검증
            handleCsrf($_POST['_csrf_token'] ?? '');
            handleDelete($db);
            break;

        default:
            throw new Exception('지원하지 않는 메소드입니다.');
    }

} catch (Exception $e) {
    handleError($e);
}

/**
 * 상품 상세정보 조회 (GET)
 */
function handleGet($db) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('상품 ID가 필요합니다.');
    }

    $sql = "
        SELECT p.*, c.name as company_name 
        FROM products p 
        LEFT JOIN companies c ON p.company_id = c.id 
        WHERE p.id = :id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('존재하지 않는 상품입니다.');
    }

    header('Content-Type: application/json');
    echo json_encode($product);
    exit;
}

/**
 * 상품 생성 (POST)
 */
function handlePost($db) {
    // 필수 필드 검증
    validateFields(['company_id', 'name', 'price', 'discounted_price']);

    $sql = "INSERT INTO products (company_id, name, price, discounted_price, discount_format) 
            VALUES (:company_id, :name, :price, :discounted_price, :discount_format)";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        'company_id' => $_POST['company_id'],
        'name' => $_POST['name'],
        'price' => $_POST['price'],
        'discounted_price' => $_POST['discounted_price'],
        'discount_format' => $_POST['discount_format']
    ]);

    if ($result) {
        header('Location: /admin/product');
        exit;
    }
}

/**
 * 상품 수정 (PUT)
 */
function handlePut($db, $_PUT) {
    // ID 확인
    if (!isset($_PUT['id'])) {
        throw new Exception('상품 ID가 필요합니다.');
    }

    validateFields(['name', 'price', 'discounted_price'], $_PUT);

    $sql = "UPDATE products 
            SET name = :name, 
                price = :price, 
                discounted_price = :discounted_price,
                discount_format = :discount_format,
                updated_dt = NOW()
            WHERE id = :id";

    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        'id' => $_PUT['id'],
        'name' => $_PUT['name'],
        'price' => $_PUT['price'],
        'discounted_price' => $_PUT['discounted_price'],
        // 주의: PUT에서는 $_POST가 아닌 $_PUT에서 받아와야 합니다.
        'discount_format' => $_PUT['discount_format'] ?? null
    ]);

    if ($result) {
        header('Location: /admin/product');
        exit;
    }
}

/**
 * 상품 삭제 (DELETE)
 */
function handleDelete($db) {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        throw new Exception('삭제할 상품 ID가 필요합니다.');
    }

    // 상품 존재 여부 확인
    $checkStmt = $db->prepare("SELECT id FROM products WHERE id = ?");
    $checkStmt->execute([$id]);
    if (!$checkStmt->fetch()) {
        throw new Exception('존재하지 않는 상품입니다.');
    }

    $sql = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute(['id' => $id]);

    if ($result) {
        header('Location: /admin/product');
        exit;
    }

    throw new Exception('상품 삭제에 실패했습니다.');
}

/**
 * 필수 필드 검증
 * @throws Exception
 */
function validateFields($required_fields, $data = null): bool
{
    $data = $data ?? $_POST;
    $errors = [];

    // 필수 필드 검증
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "{$field}은(는) 필수 입력항목입니다.";
        }
    }

    // 가격 유효성 검사 (할인가가 원가보다 큰 경우)
    if (!empty($data['price']) && !empty($data['discounted_price'])) {
        if ($data['discounted_price'] > $data['price']) {
            $errors[] = "할인가격은 원래 가격보다 클 수 없습니다.";
        }
    }

    if (!empty($errors)) {
        // 여러 에러가 있을 수 있으므로 JSON 으로 묶어 반환
        throw new Exception(json_encode($errors, JSON_UNESCAPED_UNICODE));
    }

    return true;
}

/**
 * CSRF 토큰 검증
 * @throws Exception
 */
function handleCsrf($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception('CSRF 토큰 검증 실패');
    }
}

/**
 * 에러 핸들링 함수
 */
function handleError($e)
{
    error_log($e->getMessage());

    $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/product';

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