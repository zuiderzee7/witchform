<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();

    // HTTP 메소드 확인
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
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
    error_log($e->getMessage());
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=' . urlencode($e->getMessage()));
    exit;
}

function handlePost($db) {
    // 필수 필드 검증
    validateFields(['name', 'price', 'discounted_price']);

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
        header('Location: /admin/product?success=true');
        exit;
    }
}

function handlePut($db) {
    // PUT 요청의 데이터 파싱
    parse_str(file_get_contents("php://input"), $_PUT);

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
        'discount_format' => $_POST['discount_format']
    ]);

    if ($result) {
        header('Location: /admin/product?success=update');
        exit;
    }
}

function handleDelete($db) {
    // DELETE 요청의 데이터 파싱
    parse_str(file_get_contents("php://input"), $_DELETE);

    if (!isset($_DELETE['id'])) {
        throw new Exception('삭제할 상품 ID가 필요합니다.');
    }

    $sql = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute(['id' => $_DELETE['id']]);

    if ($result) {
        header('Location: /admin/product?success=delete');
        exit;
    }
}

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

    // 가격 유효성 검사
    if (!empty($data['price']) && !empty($data['discounted_price'])) {
        if ($data['discounted_price'] > $data['price']) {
            $errors[] = "할인가격은 원래 가격보다 클 수 없습니다.";
        }
    }

    if (!empty($errors)) {
        throw new Exception(json_encode($errors));
    }

    return true;
}
