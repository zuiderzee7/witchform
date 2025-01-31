<?php
// 세션이 시작되지 않았다면 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF 토큰 생성 (이미 생성되어 있다면 재사용)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
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
                                       data-product-id="<?= $product['id'] ?>"
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

        <div class="mt-6 text-center">
            <button type="button"
                    id="orderButton"
                    class="cursor-pointer bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-8 rounded-lg">
                주문하기
            </button>
        </div>
    </div>

    <!-- 주문 모달 폼 수정 -->
    <div id="orderModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <form id="orderForm" method="POST" action="/post/api">
                <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <input type="hidden" name="company_id" value="<?= $post['company_id'] ?>">
                <input type="hidden" name="delivery_type" id="orderDeliveryType">
                <input type="hidden" name="delivery_cost" id="finalDeliveryCost">
                <input type="hidden" name="total_amount" id="finalTotalAmount">

                <div class="space-y-4">
                    <h3 class="text-lg font-bold">주문 정보 입력</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">주문자명</label>
                        <input type="text" name="customer_name" required autocomplete="off"
                               value=""
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">이메일</label>
                        <input type="email" name="customer_email" required autocomplete="off"
                               value=""
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">연락처</label>
                        <input type="tel" name="customer_phone" required autocomplete="off"
                               value=""
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div id="deliveryAddressSection">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">우편번호</label>
                            <input type="text" name="postal_code" required autocomplete="off"
                                   value="12345" maxlength="10"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700">주소</label>
                            <input type="text" name="address" required autocomplete="off"
                                   value="서울" maxlength="50"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <div class="pt-4 border-t">
                        <button type="submit"
                                class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                            주문 완료
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 모든 필요한 DOM 요소 참조

        const elements = {
            quantityInputs: document.querySelectorAll('.quantity-input'),
            deliveryTypeInputs: document.querySelectorAll('.delivery-type'),
            orderButton: document.getElementById('orderButton'),
            orderModal: document.getElementById('orderModal'),
            orderForm: document.getElementById('orderForm'),
            totalAmount: document.getElementById('totalAmount'),
            deliveryFee: document.getElementById('deliveryFee'),
            finalAmount: document.getElementById('finalAmount'),
            finalDeliveryCost: document.getElementById('finalDeliveryCost'),
            finalTotalAmount: document.getElementById('finalTotalAmount'),
            orderDeliveryType: document.getElementById('orderDeliveryType'),
            deliveryAddressSection: document.getElementById('deliveryAddressSection'),
            addressInput: document.querySelector('input[name="address"]'),
            postalCodeInput: document.querySelector('input[name="postal_code"]')
        };

        // 상수 값 정의
        const constants = {
            deliveryCost: <?= $post['delivery_cost'] ?>,
            pickupCost: <?= is_numeric($post['pickup_cost']) ? $post['pickup_cost'] : 'null' ?>,
            freeDeliveryAmount: <?= $post['free_delivery_amount'] ?>
        };

        // 총액 계산 함수
        function calculateTotal() {
            let total = 0;
            elements.quantityInputs.forEach(input => {
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
                if (total >= constants.freeDeliveryAmount) {
                    shippingCost = 0;
                    shippingLabel = `<span class="text-blue-600">무료배송</span> (${new Intl.NumberFormat('ko-KR').format(constants.deliveryCost)}원 → 0원)`;
                } else {
                    shippingCost = constants.deliveryCost;
                    shippingLabel = `${new Intl.NumberFormat('ko-KR').format(constants.deliveryCost)}원`;
                }
            } else if (selectedDeliveryType === 'pickup') {
                shippingCost = constants.pickupCost;
                shippingLabel = constants.pickupCost === 0
                    ? '<span class="text-blue-600">무료픽업</span>'
                    : `${new Intl.NumberFormat('ko-KR').format(constants.pickupCost)}원`;
            }

            // 금액 표시 업데이트
            elements.totalAmount.textContent = new Intl.NumberFormat('ko-KR').format(total) + '원';
            elements.deliveryFee.innerHTML = shippingLabel;
            elements.finalAmount.textContent = new Intl.NumberFormat('ko-KR').format(total + shippingCost) + '원';

            return { total, shippingCost };
        }

        // 수량 입력 이벤트 처리
        elements.quantityInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                if (this.value === '' || parseInt(this.value) < 0) {
                    this.value = 0;
                }

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
        });

        // 배송 방법 변경 이벤트
        elements.deliveryTypeInputs.forEach(input => {
            input.addEventListener('change', calculateTotal);
        });

        // 주문 버튼 클릭 이벤트
        elements.orderButton.addEventListener('click', function() {
            let hasItems = false;
            const orderItems = [];

            elements.quantityInputs.forEach(input => {
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

            const { total, shippingCost } = calculateTotal();
            const selectedDeliveryType = document.querySelector('input[name="delivery_type"]:checked').value;

            // 배송 정보 설정
            elements.finalDeliveryCost.value = shippingCost;
            elements.finalTotalAmount.value = total;
            elements.orderDeliveryType.value = selectedDeliveryType;

            // 배송 방법에 따른 주소 입력 필드 처리
            if (selectedDeliveryType === 'pickup') {
                elements.deliveryAddressSection.style.display = 'none';
                elements.addressInput.removeAttribute('required');
                elements.postalCodeInput.removeAttribute('required');
            } else {
                elements.deliveryAddressSection.style.display = 'block';
                elements.addressInput.setAttribute('required', 'required');
            }

            // 주문 모달 표시
            elements.orderModal.classList.remove('hidden');
        });

        // 모달 닫기 기능
        elements.orderModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        // 폼 제출 전 유효성 검사 추가
        elements.orderForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // 선택된 상품 정보 수집
            const items = [];
            elements.quantityInputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    items.push({
                        product_id: input.dataset.productId,
                        quantity: quantity,
                        price: input.dataset.price
                    });
                }
            });

            // JSON 파싱 문제 해결을 위해 데이터 전송 방식 수정
            const itemsInput = document.createElement('input');
            itemsInput.type = 'hidden';
            itemsInput.name = 'items';
            itemsInput.value = JSON.stringify(items);
            this.appendChild(itemsInput);

            // 폼 제출
            this.submit();
        });

        // 초기 계산 실행
        calculateTotal();
    });
</script>
