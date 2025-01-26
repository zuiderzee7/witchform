<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = null;

try {
    $db = Connection::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // POST 요청 처리 (주문 생성)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleCsrf($_POST['_csrf_token'] ?? '');

        // 필수 필드 검증
        if (empty($_POST['company_id']) || empty($_POST['post_id']) ||
            empty($_POST['customer_name']) || empty($_POST['customer_email']) || empty($_POST['customer_phone'])) {
            throw new Exception('필수 입력 항목이 누락되었습니다.');
        }

        // 배송 방법에 따른 주소 필수값 체크
        if ($_POST['delivery_type'] === 'normal' && empty($_POST['address'])) {
            throw new Exception('배송지 주소를 입력해주세요.');
        }

        $db->beginTransaction();

        try {
            // 주문 번호 생성
            $orderNumber = date('YmdHis') . rand(1000, 9999);

            // orders 테이블에 주문 정보 저장
            $stmt = $db->prepare("
                INSERT INTO orders (
                    company_id, post_id, order_number, customer_name,
                    customer_email, customer_phone, delivery_type,
                    delivery_cost, postal_code, address, total_amount
                ) VALUES (
                    :company_id, :post_id, :order_number, :customer_name,
                    :customer_email, :customer_phone, :delivery_type,
                    :delivery_cost, :postal_code, :address, :total_amount
                )
            ");

            $orderParams = [
                'company_id' => $_POST['company_id'],
                'post_id' => $_POST['post_id'],
                'order_number' => $orderNumber,
                'customer_name' => $_POST['customer_name'],
                'customer_email' => $_POST['customer_email'],
                'customer_phone' => $_POST['customer_phone'],
                'delivery_type' => $_POST['delivery_type'],
                'delivery_cost' => $_POST['delivery_cost'],
                'postal_code' => $_POST['postal_code'] ?? null,
                'address' => $_POST['address'] ?? null,
                'total_amount' => $_POST['total_amount']
            ];

            $result = $stmt->execute($orderParams);

            if (!$result) {
                throw new Exception('주문 정보 저장에 실패했습니다.');
            }

            $orderId = $db->lastInsertId();

            // 주문 상품 정보 검증
            $items = json_decode($_POST['items'], true);
            if (empty($items) || !is_array($items)) {
                throw new Exception('주문 상품 정보가 없습니다.');
            }

            // order_items 테이블에 주문 상품 정보 저장
            $itemStmt = $db->prepare("
                INSERT INTO order_items (
                    post_id, order_id, product_id, quantity, price
                ) VALUES (
                    :post_id, :order_id, :product_id, :quantity, :price
                )
            ");

            foreach ($items as $item) {
                if (empty($item['product_id']) || empty($item['quantity']) || !isset($item['price'])) {
                    throw new Exception('상품 정보가 올바르지 않습니다.');
                }

                $itemParams = [
                    'post_id' => $_POST['post_id'],
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];

                $result = $itemStmt->execute($itemParams);

                if (!$result) {
                    throw new Exception('주문 상품 정보 저장에 실패했습니다.');
                }

                // 재고 수량 업데이트
                $updateStmt = $db->prepare("
                    UPDATE product_inventories 
                    SET current_inventory = current_inventory - :quantity
                    WHERE product_id = :product_id 
                    AND company_id = :company_id          /* company_id 조건 추가 */
                    AND current_inventory >= :quantity
                ");

                $updateResult = $updateStmt->execute([
                    'product_id' => $item['product_id'],
                    'company_id' => $_POST['company_id'], /* company_id 파라미터 추가 */
                    'quantity' => $item['quantity']
                ]);

                if (!$updateResult || $updateStmt->rowCount() === 0) {
                    throw new Exception('재고 수량이 부족하거나 업데이트에 실패했습니다.');
                }
            }

            $db->commit();

            // 성공 응답
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'order_number' => $orderNumber,
                'redirect' => '/admin/post'
            ]);
            exit;

        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    } else {
        throw new Exception('지원하지 않는 메소드입니다.');
    }

} catch (Exception $e) {
    error_log($e->getMessage());

    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() . ' line : '. $e->getLine()
    ]);
    exit;
}

function handleCsrf($token) {
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception('CSRF 토큰이 유효하지 않거나 없습니다.');
    }
}
