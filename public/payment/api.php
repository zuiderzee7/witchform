<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;
use Payments\Toss;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = Connection::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $toss = Toss::getInstance();

    $method = $_SERVER['REQUEST_METHOD'];
    $data = [];
    $action = '';

    // POST 요청일 경우 JSON 데이터 파싱
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
    }else if ($method === 'GET' && $_GET['action'] === 'success' && isset($_GET['orderId'], $_GET['paymentKey'], $_GET['amount'])) {
        $action = $_GET['action'];
        $data = $_GET;
    }

    switch (true) {
        case $method === 'POST' && $action === 'initiate':
            handlePaymentInitiate($db, $toss, $data);
            break;
        case $method === 'GET' && $action === 'success':
            handlePaymentSuccess($db, $toss, $_GET); // 결제 성공 처리
            break;
        case $method === 'POST' && $action === 'cancel':
            handlePaymentCancel($db, $toss, $data); // 결제 취소 처리
            break;
        case $method === 'GET' && $action === 'fail':
            handlePaymentFail($db, $data); // 결제 실패 처리
            break;
        default:
            throw new Exception('지원하지 않는 요청입니다.');
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

// 주문 후 결제 진행, 리턴 값 기반 결제 처리 진행
function handlePaymentInitiate($db, $toss, $data) {
    $orderNumber = $data['order_number'] ?? null;
    $amount = $data['amount'] ?? null;

    if (!$orderNumber || !$amount) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    // 주문 정보 검증
    $stmt = $db->prepare("
        SELECT *
        FROM orders
        WHERE order_number = :order_number 
        AND order_status = 'pending'
    ");
    $stmt->execute(['order_number' => $orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('유효하지 않은 주문입니다.');
    }

    // 결제 정보 생성
    $stmt = $db->prepare("
        INSERT INTO payments (
            order_id, payment_type, amount, status
        ) VALUES (
            :order_id, 'CARD', :amount, 'ready'
        )
    ");

    $stmt->execute([
        'order_id' => $order['id'],
        'amount' => $amount
    ]);

    header('Content-Type: application/json');
    echo json_encode($toss->initiatePayment($data));
    exit;
}

//결제 완료 처리 시
function handlePaymentSuccess($db, $toss, $params) {
    $paymentKey = $params['paymentKey'] ?? null;
    $orderId = $params['orderId'] ?? null;
    $amount = $params['amount'] ?? null;

    if (!$paymentKey || !$orderId || !$amount) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    $db->beginTransaction();
    try {
        $result = $toss->approve($paymentKey, $orderId, $amount);

        // payments 테이블 업데이트
        $stmt = $db->prepare("
            UPDATE payments 
            SET mid = :mid,
                status = 'paid',
                paid_at = NOW(),
                tax_amount = :tax_amount
            WHERE order_id = (
                SELECT id FROM orders WHERE order_number = :order_number
            )
        ");

        $stmt->execute([
            'mid' => $paymentKey,
            'tax_amount' => $result['taxAmount'] ?? null,
            'order_number' => $orderId
        ]);

        // 주문 상태 업데이트
        $stmt = $db->prepare("
            UPDATE orders 
            SET order_status = 'paid' 
            WHERE order_number = :order_number
        ");
        $stmt->execute(['order_number' => $orderId]);

        $db->commit();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'redirect' => '/payment/complete?order_id=' . $orderId
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

//결제 취소 처리
function handlePaymentCancel($db, $toss, $data) {
    $orderNumber = $data['order_number'] ?? null;

    if (!$orderNumber) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    // 결제 정보 조회
    $stmt = $db->prepare("
        SELECT p.*, o.order_number, o.id as order_id
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        WHERE o.order_number = :order_number 
        AND p.status = 'paid'
    ");
    $stmt->execute(['order_number' => $orderNumber]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception('취소할 수 없는 결제입니다.');
    }

    $db->beginTransaction();
    try {
        $result = $toss->cancel($payment['mid']);

        // 결제 상태 업데이트
        $stmt = $db->prepare("
            UPDATE payments 
            SET status = 'cancelled',
                cancelled_at = NOW(),
                cancelled_amount = :amount,
                cancel_reason = :cancel_reason
            WHERE id = :payment_id
        ");
        $stmt->execute([
            'payment_id' => $payment['id'],
            'amount' => $payment['amount'],
            'cancel_reason' => $data['reason'] ?? '고객 요청'
        ]);

        // 주문 상태 업데이트
        $stmt = $db->prepare("
            UPDATE orders 
            SET order_status = 'cancelled'
            WHERE id = :order_id
        ");
        $stmt->execute(['order_id' => $payment['order_id']]);

        $db->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// 결제 실패 시
function handlePaymentFail($db, $data) {
    $orderId = $data['orderId'] ?? null;
    $message = $data['message'] ?? '결제 실패';

    if (!$orderId) {
        throw new Exception('주문 번호가 필요합니다.');
    }

    $db->beginTransaction();
    try {
        // payments 테이블 업데이트
        $stmt = $db->prepare("
            UPDATE payments 
            SET status = 'failed',
                fail_reason = :fail_reason
            WHERE order_id = (
                SELECT id FROM orders WHERE order_number = :order_number
            )
        ");

        $stmt->execute([
            'fail_reason' => $message,
            'order_number' => $orderId
        ]);

        $db->commit();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'redirect' => '/payment/fail?order_id=' . $orderId
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}