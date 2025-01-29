<?php
namespace Payments;

use Dotenv\Dotenv;
use Exception;

class Toss
{
    private static $instance = null;
    private $apiKey;
    private $apiUrl;
    private $clientKey;

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(BASE_PATH . '/');
        $dotenv->safeLoad();

        $config = require BASE_PATH . '/config/toss.php';

        $this->apiKey = $config['TOSS_SECRET_KEY'];
        $this->clientKey = $config['TOSS_CLIENT_KEY'];
        $this->apiUrl = $config['TOSS_API_URL'];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __clone() {}
    private function __wakeup() {}

    //결제 생성
    public function initiatePayment($orderData): array
    {
        $paymentData = [
            'amount' => $orderData['amount'],
            'orderId' => $orderData['order_number'],
            'orderName' =>  '주문 #' . $orderData['order_number'],
            'successUrl' => $this->getSuccessUrl(),
            'failUrl' => $this->getFailUrl()
        ];

        return [
            'success' => true,
            'clientKey' => $this->getClientKey(),
            'paymentData' => $paymentData
        ];
    }

    public function approve($paymentKey, $orderId, $amount)
    {
        $response = $this->request(
            "/payments/{$paymentKey}",
            'POST',
            [
                'orderId' => $orderId,
                'amount' => $amount
            ]
        );

        if (!isset($response['paymentKey'])) {
            throw new Exception('결제 승인 실패');
        }

        return $response;
    }

    public function cancel($paymentKey, $cancelReason = '고객 요청')
    {
        $response = $this->request(
            "/payments/{$paymentKey}/cancel",
            'POST',
            ['cancelReason' => $cancelReason]
        );

        if (!isset($response['paymentKey'])) {
            throw new Exception('결제 취소 실패');
        }

        return $response;
    }

    private function request($endpoint, $method = 'GET', $data = null)
    {
        $ch = curl_init();
        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':'),
            'Content-Type: application/json'
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false // 개발 환경에서만 사용
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('API 요청 실패: ' . curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new Exception(
                isset($result['message']) ? $result['message'] : '알 수 없는 오류가 발생했습니다.'
            );
        }

        return $result;
    }

    private function getSuccessUrl(): string
    {
        return $_ENV['APP_URL'] . '/payment/api?action=success';
    }

    private function getFailUrl(): string
    {
        return $_ENV['APP_URL'] . '/payment/fail';
    }

    public function getClientKey()
    {
        return $this->clientKey;
    }
}