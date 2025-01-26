<main class="container mx-auto p-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="text-gray-600 mb-4">
                <p>업체명: <?= htmlspecialchars($post['company_name']) ?></p>
                <p>등록일: <?= (new DateTime($post['created_dt']))
                        ->setTimezone(new DateTimeZone('Asia/Seoul'))
                        ->format('Y-m-d H:i:s') ?></p>
            </div>
            <div class="prose max-w-none">
                <?= nl2br(htmlspecialchars($post['content'])) ?>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">배송 정보</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-gray-600">픽업 비용</p>
                    <p class="font-medium">
                        <?php if(is_numeric($post['pickup_cost'])):?>
                            <?= $post['pickup_cost'] === 0 ? '무료' : number_format($post['pickup_cost']).'원' ?>
                        <?php else: ?>
                            불가
                        <?php endif?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-600">배송비</p>
                    <p class="font-medium"><?= number_format($post['delivery_cost']) ?>원</p>
                </div>
                <div>
                    <p class="text-gray-600">무료배송 기준금액</p>
                    <p class="font-medium"><?= number_format($post['free_delivery_amount']) ?>원</p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4">구매 가능 상품</h2>
            <div class="space-y-4">
                <?php foreach ($products as $product): ?>
                    <div class="border rounded p-4">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-medium"><?= htmlspecialchars($product['name']) ?></h3>
                            <span class="text-gray-600">
                        총 재고: <?= number_format($product['total_inventory']) ?>개
                        / 남은 재고: <?= number_format($product['current_inventory']) ?>개
                    </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-600">
                                    원가: <?= number_format($product['price']) ?>원
                                </p>
                                <?php if ($product['discounted_price']): ?>
                                    <p class="text-red-600">
                                        <?php
                                        $discountAmount = $product['price'] - $product['discounted_price'];
                                        $discountRate = round(($discountAmount / $product['price']) * 100);
                                        ?>
                                        할인가: <?= number_format($product['discounted_price']) ?>원
                                        (<?= $product['discount_format'] === '%' ? "{$discountRate}%" : number_format($discountAmount).'원' ?> 할인)
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-600">수량:</label>
                                <input type="number"
                                       class="quantity-input w-20 px-2 py-1 border rounded"
                                       data-price="<?= $product['discounted_price'] ?: $product['price'] ?>"
                                       data-max="<?= $product['current_inventory'] ?>"
                                       min="0"
                                       max="<?= $product['current_inventory'] ?>"
                                       value="0">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">배송 방법 선택</h2>
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <input type="radio"
                               id="delivery_normal"
                               name="delivery_type"
                               value="normal"
                               class="delivery-type"
                               checked>
                        <label for="delivery_normal" class="flex-1">
                            <span class="font-medium">일반 배송</span>
                            <span class="text-sm text-gray-600 block">
                                기본 배송비: <?= number_format($post['delivery_cost']) ?>원
                                <?php if($post['free_delivery_amount'] > 0): ?>
                                    (<?= number_format($post['free_delivery_amount']) ?>원 이상 주문 시 무료배송)
                                <?php endif; ?>
                            </span>
                        </label>
                    </div>

                    <?php if(is_numeric($post['pickup_cost'])): ?>
                        <div class="flex items-center space-x-3">
                            <input type="radio"
                                   id="delivery_pickup"
                                   name="delivery_type"
                                   value="pickup"
                                   class="delivery-type"
                                <?= $post['pickup_cost'] === null ? 'disabled' : '' ?>>
                            <label for="delivery_pickup" class="flex-1">
                                <span class="font-medium">매장 픽업</span>
                                <span class="text-sm text-gray-600 block">
                                    픽업 비용: <?= $post['pickup_cost'] === 0 ? '무료' : number_format($post['pickup_cost']).'원' ?>
                                </span>
                            </label>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-3 opacity-50">
                            <input type="radio" disabled>
                            <label class="flex-1">
                                <span class="font-medium">매장 픽업</span>
                                <span class="text-sm text-gray-600 block">픽업 불가 매장</span>
                            </label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 p-4 bg-gray-50 rounded-lg space-y-2">
                <div class="flex justify-between items-center">
                    <span class="font-medium">총 주문 금액:</span>
                    <span class="text-xl font-bold text-blue-500" id="totalAmount">0원</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-medium">배송비:</span>
                    <span class="text-lg font-medium" id="deliveryFee">
                        <?= number_format($post['delivery_cost']) ?>원
                    </span>
                </div>
                <div class="flex justify-between items-center border-t pt-2">
                    <span class="font-medium">최종 결제 금액:</span>
                    <span class="text-xl font-bold text-red-500" id="finalAmount">0원</span>
                </div>
            </div>
        </div>

    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInputs = document.querySelectorAll('.quantity-input');
        const deliveryTypeInputs = document.querySelectorAll('.delivery-type');
        const deliveryCost = <?= $post['delivery_cost'] ?>;
        const pickupCost = <?= is_numeric($post['pickup_cost']) ? $post['pickup_cost'] : 'null' ?>;
        const freeDeliveryAmount = <?= $post['free_delivery_amount'] ?>;

        function calculateTotal() {
            let total = 0;
            // 상품 금액 계산
            quantityInputs.forEach(input => {
                const price = parseInt(input.dataset.price);
                const quantity = parseInt(input.value) || 0;
                total += price * quantity;
            });

            // 선택된 배송 방법 확인
            const selectedDeliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
            let shippingCost = 0;
            let shippingLabel = '';

            // 배송비 계산
            if (selectedDeliveryType === 'normal') {
                if (total >= freeDeliveryAmount) {
                    shippingCost = 0;
                    shippingLabel = `<span class="text-blue-600">무료배송</span> (${new Intl.NumberFormat('ko-KR').format(deliveryCost)}원 → 0원)`;
                } else {
                    shippingCost = deliveryCost;
                    shippingLabel = `${new Intl.NumberFormat('ko-KR').format(deliveryCost)}원`;
                }
            } else if (selectedDeliveryType === 'pickup') {
                shippingCost = pickupCost;
                shippingLabel = pickupCost === 0
                    ? '<span class="text-blue-600">무료픽업</span>'
                    : `${new Intl.NumberFormat('ko-KR').format(pickupCost)}원`;
            }

            // 금액 표시 업데이트
            document.getElementById('totalAmount').textContent =
                new Intl.NumberFormat('ko-KR').format(total) + '원';
            document.getElementById('deliveryFee').innerHTML = shippingLabel;
            document.getElementById('finalAmount').textContent =
                new Intl.NumberFormat('ko-KR').format(total + shippingCost) + '원';
        }

        // 배송 방법 변경 이벤트
        deliveryTypeInputs.forEach(input => {
            input.addEventListener('change', calculateTotal);
        });

        quantityInputs.forEach(input => {
            // 입력값 변경 이벤트
            input.addEventListener('input', function(e) {
                // 입력값이 비어있거나 음수인 경우 0으로 설정
                if (this.value === '' || parseInt(this.value) < 0) {
                    this.value = 0;
                }

                // 최대 재고 수량 체크
                const maxQuantity = parseInt(this.dataset.max);
                if (parseInt(this.value) > maxQuantity) {
                    alert(`최대 ${maxQuantity}개까지만 선택 가능합니다.`);
                    this.value = maxQuantity;
                }

                calculateTotal();
            });

            // 숫자 키 입력만 허용
            input.addEventListener('keypress', function(e) {
                if (!/[\d]/.test(e.key)) {
                    e.preventDefault();
                }
            });

            // 붙여넣기 시 숫자만 허용
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text');
                if (/^\d+$/.test(pastedData)) {
                    const newValue = Math.min(parseInt(pastedData), parseInt(this.dataset.max));
                    this.value = newValue;
                    calculateTotal();
                }
            });

            // 포커스 아웃 시 유효성 검사
            input.addEventListener('blur', function() {
                const value = parseInt(this.value) || 0;
                const maxQuantity = parseInt(this.dataset.max);

                if (value > maxQuantity) {
                    alert(`최대 ${maxQuantity}개까지만 선택 가능합니다.`);
                    this.value = maxQuantity;
                    calculateTotal();
                }
            });

            // 초기 계산 실행
            calculateTotal();
        });

        // 주문하기 버튼 클릭 이벤트 (필요한 경우)
        document.getElementById('orderButton')?.addEventListener('click', function() {
            let hasItems = false;
            const orderItems = [];

            quantityInputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    hasItems = true;
                    orderItems.push({
                        productId: input.dataset.productId,
                        quantity: quantity,
                        price: input.dataset.price
                    });
                }
            });

            if (!hasItems) {
                alert('최소 1개 이상의 상품을 선택해주세요.');
                return;
            }

            // 여기에 주문 처리 로직 추가
            const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
            console.log('주문 정보:', {
                deliveryType: deliveryType,
                items: orderItems
            });
        });
    });
</script>
