<?php
require_once $_SERVER['DOCUMENT_ROOT']. '/../src/bootstrap.php';
use Database\Connection;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = Connection::getInstance()->getConnection();
    $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'POST':
            handleCsrf($_POST['_csrf_token'] ?? '');
            handlePost($db);
            break;

        case 'PUT':
            parse_str(file_get_contents("php://input"), $_PUT);
            handleCsrf($_PUT['_csrf_token'] ?? '');
            handlePut($db, $_PUT);
            break;

        case 'DELETE':
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
 * 게시글 생성 (POST)
 */
function handlePost($db) {
    try {
        validateFields(['title', 'company_id']);

        $db->beginTransaction();

        // 1. posts 테이블 저장
        $stmt = $db->prepare("
            INSERT INTO posts (company_id, title, content)
            VALUES (:company_id, :title, :content)
        ");
        $stmt->execute([
            'company_id' => $_POST['company_id'],
            'title'      => $_POST['title'],
            'content'    => $_POST['content'] ?? ''
        ]);
        $post_id = $db->lastInsertId();

        // 2. post_delivery 테이블 (옵션)
        if (!empty($_POST['delivery_cost'])) {
            $stmt = $db->prepare("
                INSERT INTO post_delivery (
                    company_id, post_id, pickup_cost, delivery_cost,
                    free_delivery_amount
                ) VALUES (
                    :company_id, :post_id, :pickup_cost, :delivery_cost,
                    :free_delivery_amount
                )
            ");
            $stmt->execute([
                'company_id'           => $_POST['company_id'],
                'post_id'              => $post_id,
                'pickup_cost'          => $_POST['pickup_cost'] ?? null,
                'delivery_cost'        => $_POST['delivery_cost'],
                'free_delivery_amount' => $_POST['free_delivery_amount'] ?? null,
            ]);
        }

        // 3. post_products 테이블 (연결 상품)
        if (!empty($_POST['product_ids'])) {
            $stmt = $db->prepare("
                INSERT INTO post_products (company_id, post_id, product_id)
                VALUES (:company_id, :post_id, :product_id)
            ");
            foreach ($_POST['product_ids'] as $product_id) {
                $stmt->execute([
                    'company_id' => $_POST['company_id'],
                    'post_id'    => $post_id,
                    'product_id' => $product_id
                ]);
            }
        }

        $db->commit();
        header('Location: /admin/post');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * 게시글 수정 (PUT)
 */
function handlePut($db, $_PUT) {
    try {
        if (empty($_PUT['id'])) {
            throw new Exception('게시글 ID가 필요합니다.');
        }
        validateFields(['title', 'company_id'], $_PUT);

        $db->beginTransaction();

        // 1. posts 테이블 수정
        $stmt = $db->prepare("
            UPDATE posts 
               SET title      = :title,
                   content    = :content,
                   company_id = :company_id,
                   updated_dt = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            'id'         => $_PUT['id'],
            'title'      => $_PUT['title'],
            'content'    => $_PUT['content'] ?? '',
            'company_id' => $_PUT['company_id']
        ]);

        // 2. post_delivery 테이블 수정/등록(UPSERT)
        if (!empty($_PUT['delivery_cost'])) {
            $stmt = $db->prepare("
                INSERT INTO post_delivery (
                    company_id, post_id, pickup_cost, delivery_cost,
                    free_delivery_amount
                ) VALUES (
                    :company_id, :post_id, :pickup_cost, :delivery_cost,
                    :free_delivery_amount
                ) ON DUPLICATE KEY UPDATE
                    pickup_cost = VALUES(pickup_cost),
                    delivery_cost = VALUES(delivery_cost),
                    free_delivery_amount = VALUES(free_delivery_amount)
            ");

            $stmt->execute([
                'company_id' => $_PUT['company_id'],
                'post_id' => $_PUT['id'],
                'pickup_cost' => !empty($_PUT['pickup_cost']) ? (int)$_PUT['pickup_cost'] : null,
                'delivery_cost' => $_PUT['delivery_cost'],
                'free_delivery_amount' => !empty($_PUT['free_delivery_amount']) ? (int)$_PUT['free_delivery_amount'] : 0
            ]);
        }

        // 3. post_products (연결 상품) 재등록
        if (!empty($_PUT['product_ids'])) {
            // 기존 연결된 상품 목록 조회
            $stmt = $db->prepare("SELECT product_id FROM post_products WHERE post_id = :post_id");
            $stmt->execute(['post_id' => $_PUT['id']]);
            $existingProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // 새로운 상품 ID 배열
            $newProducts = $_PUT['product_ids'];

            // 삭제할 상품 (기존에 있었지만 새로운 목록에 없는 것)
            $productsToDelete = array_diff($existingProducts, $newProducts);
            if (!empty($productsToDelete)) {
                $stmt = $db->prepare("
            DELETE FROM post_products 
            WHERE post_id = :post_id AND product_id IN (" . implode(',', $productsToDelete) . ")
        ");
                $stmt->execute(['post_id' => $_PUT['id']]);
            }

            // 추가할 상품 (새로운 목록에 있지만 기존에 없던 것)
            $productsToAdd = array_diff($newProducts, $existingProducts);
            if (!empty($productsToAdd)) {
                $stmt = $db->prepare("
            INSERT INTO post_products (company_id, post_id, product_id)
            VALUES (:company_id, :post_id, :product_id)
        ");
                foreach ($productsToAdd as $product_id) {
                    $stmt->execute([
                        'company_id' => $_PUT['company_id'],
                        'post_id' => $_PUT['id'],
                        'product_id' => $product_id
                    ]);
                }
            }
        }

        $db->commit();

        header('Location: /admin/post');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * 게시글 삭제 (DELETE)
 */
function handleDelete($db) {
    try {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            throw new Exception('삭제할 게시글 ID가 필요합니다.');
        }

        $db->beginTransaction();

        // 연관 테이블 삭제
        $stmt = $db->prepare("DELETE FROM post_products WHERE post_id = :id");
        $stmt->execute(['id' => $id]);

        $stmt = $db->prepare("DELETE FROM post_delivery WHERE post_id = :id");
        $stmt->execute(['id' => $id]);

        //게시글 삭제
        $stmt = $db->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $db->commit();
        header('Location: /admin/post');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
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
function handleError($e) {
    error_log($e->getMessage());

    $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/post';

    // 이미 쿼리 파라미터가 있는지 확인
    if (strpos($referer, '?') !== false) {
        // 기존 파라미터 있음 -> &로 연결
        $redirectUrl = $referer . '&error=' . urlencode($e->getMessage());
    } else {
        // 파라미터 없음 -> ?로 연결
        $redirectUrl = $referer . '?error=' . urlencode($e->getMessage());
    }

    header('Location: ' . $redirectUrl);
    exit;
}