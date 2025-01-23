<?php
require_once __DIR__ . '/../src/Database/Connection.php';
use Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();
    echo "데이터베이스 연결 테스트";
} catch (Exception $e) {
    echo "연결 실패: " . $e->getMessage();
}