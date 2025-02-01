<main class="container mx-auto p-4 divide-y divide-y-black space-y-4">
    <section class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">게시물 리스트 (최근5개)</h2>
        </div>

        <ul class="divide-y divide-gray-200">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <li class="py-4 hover:bg-gray-50 cursor-pointer transition-colors"
                        onclick="location.href='/post/detail?id=<?= $post['id'] ?>'">
                        <div class="flex flex-col space-y-2">
                            <h3 class="font-bold text-lg text-gray-800">
                                <?=$post['id']?>) <?= htmlspecialchars($post['title']) ?>
                            </h3>
                            <div class="flex justify-between items-center text-sm text-gray-600">
                                <span><?= htmlspecialchars($post['company_name']) ?></span>
                                <time datetime="<?= $post['created_dt'] ?>">
                                    <?= (new DateTime($post['created_dt']))
                                        ->setTimezone(new DateTimeZone('Asia/Seoul'))
                                        ->format('Y-m-d H:i:s')
                                    ?>
                                </time>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="py-4 text-center text-gray-500">등록된 게시물이 없습니다.</li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">주문&결제 리스트</h2>
        </div>

        <ul class="divide-y divide-gray-200">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                    // 해당 주문의 결제 정보 찾기
                    $payment = array_filter($payments, function($p) use ($order) {
                        return $p['id'] === $order['id'];
                    });
                    $payment = !empty($payment) ? reset($payment) : null;

                    // 상태 결정 (결제 상태 우선)
                    $status = $statusMap[$order['order_status']] ?? ['label' => '확인중', 'class' => 'bg-gray-100 text-gray-800'];
                    $payment_status = $statusMap[$order['payment_status']] ?? ['label' => '확인중', 'class' => 'bg-gray-100 text-gray-800'];
                    ?>
                    <li class="py-4">
                        <div class="flex flex-col space-y-4">
                            <!-- 주문 기본 정보 -->
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $status['class'] ?>">
                                            <?= $status['label'] ?>
                                        </span>
                                        <span class="font-medium">
                                            주문번호: <?=$order['id']?>) <?= htmlspecialchars($order['order_number']) ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <?= isset($order['customer_name']) ? htmlspecialchars(\Utils\MaskingUtil::name($order['customer_name'])) : '' ?>
                                        <?= isset($order['customer_email']) ? htmlspecialchars(\Utils\MaskingUtil::email($order['customer_email'])) : '' ?>
                                        <?= isset($order['customer_phone']) ? htmlspecialchars(\Utils\MaskingUtil::phone($order['customer_phone'])) : '' ?>
                                    </p>
                                </div>
                            </div>

                            <!-- 결제 정보 (있는 경우) -->
                            <?php if (isset($order['payment_mid'])): ?>
                                <div class="bg-gray-50 rounded-lg p-3 ml-4">
                                    <div class="flex justify-between items-center">
                                        <div class="space-x-2">
                                            <span class="mr-2 px-2 py-1 text-xs font-medium rounded-full <?= $status['class'] ?>">
                                                <?= $payment_status['label'] ?> <?=$order['payment_status']?>
                                            </span>
                                            <span class="text-sm font-medium">
                                                결제번호: <?= htmlspecialchars($order['payment_mid']) ?>
                                            </span>
                                            <p class="text-sm text-gray-600">
                                                결제일시: <?= (new DateTime($order['payment_created_dt']))->format('Y-m-d H:i:s') ?>
                                            </p>
                                        </div>
                                        <?php if($order['payment_status'] === 'paid'): ?>
                                            <button onclick="cancelPayment('<?=$order['order_number']?>', '<?= $order['payment_mid'] ?>')"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-2 py-2 rounded-md text-sm whitespace-pre"
                                            >결제취소</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- 주문 상품 목록 -->
                            <?php if (isset($orderItems[$order['id']])): ?>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <ul class="space-y-2">
                                        <li>
                                            <span class="font-bold"><?= htmlspecialchars($order['company_name']) ?></span>
                                        </li>
                                        <?php foreach ($orderItems[$order['id']] as $orderItem): ?>
                                            <li class="flex justify-between text-sm">
                                                <span class="font-medium"><?= htmlspecialchars($orderItem['product_name']) ?></span>
                                                <div class="space-x-4">
                                                    <span><?= number_format($orderItem['quantity']) ?>개</span>
                                                    <span class="font-semibold"><?= number_format($orderItem['price']) ?>원</span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-end text-sm text-gray-600">
                                <span class="inline-flex items-center">
                                    <?php if (isset($order['delivery_type'])): ?>
                                        <?php if ($order['delivery_type'] === 'normal'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                </svg>
                                                배송비
                                                <span class="ml-2 font-medium">
                                                    <?= isset($order['delivery_cost']) ? ($order['delivery_cost'] === 0 ? '무료' : number_format($order['delivery_cost']).'원') : '' ?>
                                                </span>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-green-100 text-green-800">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                픽업
                                                <span class="ml-2 font-medium">
                                                    <?= isset($order['delivery_cost']) ? ($order['delivery_cost'] === 0 ? '무료' : number_format($order['delivery_cost']).'원') : '' ?>
                                                </span>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="flex justify-end items-center space-x-4 pt-2">
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">상품 금액</p>
                                    <p class="font-medium">
                                        <?= isset($order['total_amount']) ? number_format($order['total_amount']) : 0 ?>원
                                    </p>
                                </div>

                                <div class="text-right border-l pl-4">
                                    <p class="text-sm font-medium text-gray-800">최종 결제 금액</p>
                                    <p class="text-lg font-bold text-blue-600">
                                        <?= isset($order['total_amount']) ? number_format($order['total_amount'] + ($order['delivery_cost'] ?? 0)) : 0 ?>원
                                    </p>
                                </div>
                            </div>

                            <?php if($order['order_status'] === 'pending'): ?>
                                <button type="button"
                                        class="cursor-pointer bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm"
                                        onclick="initiatePayment('<?=$order['order_number']?>', <?= $order['total_amount']+$order['delivery_cost'] ?>)">
                                    결제하기
                                </button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="py-4 text-center text-gray-500">주문 내역이 없습니다.</li>
            <?php endif; ?>
        </ul>

        <!-- 페이지네이션 -->
        <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="flex justify-center space-x-2 mt-6">
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <a href="?page=<?= $i ?>"
                       class="px-4 py-2 rounded-md <?= $i === $pagination['currentPage'] ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </section>

</main>
<script src="https://js.tosspayments.com/v1"></script>
<script>
    const tossPayments = TossPayments('<?=$_ENV['TOSS_CLIENT_KEY']?>');
    function initiatePayment(orderNumber, totalAmount) {
        fetch('/payment/api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: 'initiate',
                order_number: orderNumber,
                amount: totalAmount
            })
        })
            .then(async response => {
                // 응답 텍스트를 먼저 확인
                const text = await response.text();
                //console.log('Response:', text);
                try {
                    // 텍스트를 JSON으로 파싱
                    const data = JSON.parse(text);
                    if (!response.ok) {
                        throw new Error(data.message || '결제 요청 실패');
                    }
                    return data;
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    throw new Error('서버 응답을 처리할 수 없습니다.');
                }
            })
            .then(data => {
                //console.log('Data:', data);
                if (data.success && data.paymentData) {
                    return tossPayments.requestPayment('카드', {
                        amount: data.paymentData.amount,
                        orderId: data.paymentData.orderId,
                        orderName: data.paymentData.orderName,
                        successUrl: data.paymentData.successUrl,
                        failUrl: data.paymentData.failUrl
                    });
                } else {
                    throw new Error(data.message || '결제 초기화 실패');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || '결제 처리 중 오류가 발생했습니다.');
            });
    }


    function cancelPayment(orderNumber) {
        fetch('/payment/api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'cancel',
                order_number: orderNumber,
            })
        })
            .then(async response => {
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert('결제가 취소되었습니다.');
                        location.reload();
                    } else {
                        throw new Error(data.message || '결제 취소에 실패했습니다.');
                    }
                } catch (e) {
                    console.error('Cancel Response Parse Error:', e);
                    throw new Error(e);
                }
            })
            .catch(error => {
                console.error('Cancel Error:', error);
                alert(error.message || '결제 취소 처리 중 오류가 발생했습니다.');
            });
    }
</script>