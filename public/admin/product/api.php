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
 * @param $db
 * @throws Exception
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
 * @param $db
 * @throws Exception
 */
function handlePost($db) {
    try {
        validateFields(['company_id', 'name', 'price', 'discounted_price']);

        $db->beginTransaction();

        // 1. products 테이블 저장
        $stmt = $db->prepare("
            INSERT INTO products (
                company_id, name, price, discounted_price, discount_format
            ) VALUES (
                :company_id, :name, :price, :discounted_price, :discount_format
            )
        ");

        $stmt->execute([
            'company_id' => $_POST['company_id'],
            'name' => $_POST['name'],
            'price' => $_POST['price'],
            'discounted_price' => $_POST['discounted_price'],
            'discount_format' => $_POST['discount_format']
        ]);

        $product_id = $db->lastInsertId();

        // 2. product_inventories 테이블 저장
        $stmt = $db->prepare("
            INSERT INTO product_inventories (
                product_id, company_id, total_inventory, current_inventory
            ) VALUES (
                :product_id, :company_id, :total_inventory, :current_inventory
            )
        ");

        $stmt->execute([
            'product_id' => $product_id,
            'company_id' => $_POST['company_id'],
            'total_inventory' => !empty($_POST['total_inventory']) ? (int)$_POST['total_inventory'] : 0,
            'current_inventory' => !empty($_POST['current_inventory']) ? (int)$_POST['current_inventory'] : 0
        ]);

        $db->commit();
        header('Location: /admin/product');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * 상품 수정 (PUT)
 * @param $db
 * @param $_PUT
 * @throws Exception
 */
function handlePut($db, $_PUT) {
    try {
        if (!isset($_PUT['id'])) {
            throw new Exception('상품 ID가 필요합니다.');
        }

        validateFields(['name', 'price', 'discounted_price'], $_PUT);

        $db->beginTransaction();

        // 1. products 테이블 수정
        $stmt = $db->prepare("
            UPDATE products 
            SET name = :name,
                price = :price,
                discounted_price = :discounted_price,
                discount_format = :discount_format,
                updated_dt = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $_PUT['id'],
            'name' => $_PUT['name'],
            'price' => $_PUT['price'],
            'discounted_price' => $_PUT['discounted_price'],
            'discount_format' => $_PUT['discount_format'] ?? null
        ]);

        // 2. product_inventories 테이블 UPSERT
        $stmt = $db->prepare("
            INSERT INTO product_inventories (
                product_id, company_id, total_inventory, current_inventory
            ) VALUES (
                :product_id, :company_id, :total_inventory, :current_inventory
            ) ON DUPLICATE KEY UPDATE
                total_inventory = VALUES(total_inventory),
                current_inventory = VALUES(current_inventory)
        ");

        $stmt->execute([
            'product_id' => $_PUT['id'],
            'company_id' => $_PUT['company_id'],
            'total_inventory' => !empty($_PUT['total_inventory']) ? (int)$_PUT['total_inventory'] : 0,
            'current_inventory' => !empty($_PUT['current_inventory']) ? (int)$_PUT['current_inventory'] : 0
        ]);

        $db->commit();
        header('Location: /admin/product');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * 상품 삭제 (DELETE)
 * @param $db
 * @throws Exception
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
 * @param $required_fields
 * @param null $data
 * @return bool
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
 * @param $token
 * @throws Exception
 */
function handleCsrf($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception('CSRF 토큰 검증 실패');
    }
}

/**
 * 에러 핸들링 함수
 * @param $e
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