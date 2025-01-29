<main class="container mx-auto p-4 divide-y divide-y-black">
    <section class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">게시물 리스트 (최근5개)</h2>
        </div>

        <ul class="divide-y divide-gray-200">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <li class="py-4 flex justify-between items-center hover:bg-gray-50 cursor-pointer"
                        onclick="location.href='/post/detail?id=<?= $post['id'] ?>'">
                        <div class="flex-1">
                            <p class="font-bold text-lg text-gray-800">
                                <?= htmlspecialchars($post['title']) ?>
                            </p>
                            <p class="font-medium text-gray-600">
                                <?= htmlspecialchars($post['company_name']) ?>
                            </p>
                            <p class="text-sm text-gray-400">
                                <?= (new DateTime($post['created_dt']))
                                    ->setTimezone(new DateTimeZone('Asia/Seoul'))
                                    ->format('Y-m-d H:i:s')
                                ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="py-4 text-center text-gray-500">등록된 게시물이 없습니다.</li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">주문 리스트 (최근5개)</h2>
        </div>

        <ul class="divide-y divide-gray-200">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <?php $status = $statusMap[$order['order_status']] ?? ['label' => '확인중', 'class' => 'bg-gray-100 text-gray-800'];?>
                    <li class="py-4 flex justify-between items-end space-x-2">
                        <div class="flex-1">
                            <p class="font-medium">
                                주문번호: <?= htmlspecialchars($order['order_number']) ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $status['class'] ?>">
                                    <?= $status['label'] ?>
                                </span>
                            </p>
                            <p class="font-medium text-gray-600">
                                <?= htmlspecialchars(\Utils\MaskingUtil::name($order['customer_name'])) ?>
                                <?= htmlspecialchars(\Utils\MaskingUtil::email($order['customer_email'])) ?>
                                <?= htmlspecialchars(\Utils\MaskingUtil::phone($order['customer_phone'])) ?>
                            </p>
                            <div class="flex space-x-2 justify-end items-end">
                                <span class="text-xs tracking-tighter">
                                    <?= htmlspecialchars($order['delivery_type'] === 'normal' ? '배송비' : '픽업') ?>
                                    <?= htmlspecialchars($order['delivery_cost'] === 0 ? '무료' : (number_format($order['delivery_cost'])).'원') ?>
                                </span>
                                <p class="font-semibold text-lg tracking-tighter">
                                    상품 합계 <?=number_format($order['total_amount'])?>원
                                </p>
                            </div>
                        </div>
                        <?php if($order['order_status'] === 'pending') : ?>
                        <button type="button"
                                class="cursor-pointer bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded-md"
                                onclick="initiatePayment('<?=$order['order_number']?>', <?= $order['total_amount']+$order['delivery_cost'] ?>)"
                        >
                            결제하기
                        </button>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="py-4 text-center text-gray-500">주문 내역이 없습니다.</li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">결제 리스트 (최근5개)</h2>
        </div>

        <ul class="divide-y divide-gray-200">
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $payment): ?>
                    <?php $status = $statusMap[$payment['status']] ?? ['label' => '확인중', 'class' => 'bg-gray-100 text-gray-800'];?>
                    <li class="py-4 flex justify-between items-end space-x-2">
                        <div class="flex-1">
                            <p class="font-medium">
                                <?= $payment['order_id']?>
                                결제번호: <?= htmlspecialchars($payment['mid']) ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $status['class'] ?>">
                                <?= $status['label'] ?>
                            </span>
                            </p>
                            <p class="font-medium text-gray-600">
                                <?= htmlspecialchars(\Utils\MaskingUtil::name($payment['customer_name'])) ?>
                                <?= htmlspecialchars(\Utils\MaskingUtil::email($payment['customer_email'])) ?>
                                <?= htmlspecialchars(\Utils\MaskingUtil::phone($payment['customer_phone'])) ?>
                            </p>
                            <div class="flex space-x-2 justify-start items-end">
                                <p class="font-semibold text-lg tracking-tighter">
                                    결제 금액 <?=number_format($payment['total_amount'] + $payment['delivery_cost'])?>원
                                </p>
                            </div>
                        </div>
                        <?php if($payment['status'] === 'paid') :?>
                        <button onclick="cancelPayment('<?=$payment['order_number']?>', '<?= $payment['mid'] ?>')"
                                class="cursor-pointer bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded-md">
                            결제취소
                        </button>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="py-4 text-center text-gray-500">결제 내역이 없습니다.</li>
            <?php endif; ?>
        </ul>
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
                    throw new Error('서버 응답을 처리할 수 없습니다.');
                }
            })
            .catch(error => {
                console.error('Cancel Error:', error);
                alert(error.message || '결제 취소 처리 중 오류가 발생했습니다.');
            });
    }
</script>