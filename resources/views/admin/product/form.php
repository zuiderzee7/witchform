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
<style>
    .price_format{
        position: relative;
        width: 100%;
    }
    .price_format[data-format]:not([data-format=""]){
        padding-right: 20px;
    }
    .price_format[data-format]:after{
        content: attr(data-format);
        position: absolute;
        top: 0;
        right: 6px;
        display: flex;
        align-items: center;
        height: 42px;
        font-size: 14px;
    }
    [data-before]::before{
        content: attr(data-before);
        font-size: 14px;
    }
</style>

<main class="container mx-auto">
    <div class="flex flex-col sm:flex-row gap-4">
        <?php include BASE_PATH . "/resources/layouts/admin/aside.php"; ?>
        <section class="flex-1">
            <div class="flex justify-between py-2">
                <h1 class="text-lg font-bold"><?= isset($product) ? '상품 수정' : '상품 추가' ?></h1>
                <div>
                    <a href="/admin/product" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1 px-6 rounded-sm shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 inline-flex items-center">
                        목록
                    </a>
                </div>
            </div>

            <form id="productForm" action="/admin/product/api" method="POST" class="w-full max-w-4xl mx-auto p-4">
                <!-- CSRF 토큰 전송 -->
                <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">

                <?php if(isset($product)): ?>
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <?php endif; ?>

                <ul class="w-full divide-y divide-gray-200">
                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label for="company_id" class="block text-sm font-medium text-gray-700">업체 <span class="text-red-500">*</span></label>
                            <select id="company_id"
                                    name="company_id"
                                    autocomplete="off"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">선택하세요</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= $company['id'] ?>" <?= isset($product) && $product['company_id'] == $company['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($company['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </li>

                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label for="name" class="block text-sm font-medium text-gray-700">상품명 <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name"
                                   value="<?= isset($product) ? htmlspecialchars($product['name']) : '' ?>"
                                   autocomplete="off"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </li>

                    <li class="grid grid-cols-2 gap-4 py-4">
                        <div class="space-y-2">
                            <label for="price" class="block text-sm font-medium text-gray-700">가격 <span class="text-red-500">*</span></label>
                            <div class="price_format" data-format="원">
                                <input type="number" id="price" name="price"
                                       value="<?= isset($product) ? $product['price'] : '' ?>"
                                       min="0" max="99999999" autocomplete="off"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="discounted_price" class="block text-sm font-medium text-gray-700">판매가격 <span class="text-red-500">*</span></label>
                            <div class="price_format" data-format="원">
                                <input type="number" id="discounted_price" name="discounted_price"
                                       value="<?= isset($product) ? $product['discounted_price'] : '' ?>"
                                       min="0" max="99999999" autocomplete="off"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="space-y-2 space-x-2">
                            <label for="discount_format" class="block text-sm font-medium text-gray-700">할인 표기법 <span class="text-red-500">*</span></label>
                            <label>
                                –
                                <input type="radio" name="discount_format" value="-"
                                    <?= !isset($product) || $product['discount_format'] == '-' ? 'checked' : '' ?>>
                            </label>
                            <label>
                                %
                                <input type="radio" name="discount_format" value="%"
                                    <?= isset($product) && $product['discount_format'] == '%' ? 'checked' : '' ?>>
                            </label>
                        </div>
                        <div class="space-y-2 text-right">
                            <label class="block text-sm font-medium text-gray-700">할인 표기</label>
                            <span id="discount_price" class="price_format w-fit text-lg text-red-700" data-format="원">0</span>
                        </div>
                    </li>

                    <?php if(!empty($_GET['error'])): ?>
                        <li>
                            <div class="font-bold">error.</div>
                            <div class="text-red-500 text-sm"><?= htmlspecialchars($_GET['error']) ?></div>
                        </li>
                    <?php endif; ?>

                    <li class="py-4 flex justify-end space-x-2">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-6 rounded-sm shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <?= isset($product) ? '수정' : '추가' ?>
                        </button>
                    </li>
                </ul>
            </form>
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const FORMATS = {
            PERCENTAGE: '%',
            CURRENCY: '원'
        };

        // document 캐싱
        const elements = {
            form: document.getElementById('productForm'),
            company_id: document.getElementById('company_id'),
            name: document.getElementById('name'),
            price: document.getElementById('price'),
            discountedPrice: document.getElementById('discounted_price'),
            discountPrice: document.getElementById('discount_price'),
            formatRadios: document.getElementsByName('discount_format')
        };

        // 표시 데이터
        const updateDiscountDisplay = (price, discountedPrice, format) => {
            const difference = price - discountedPrice;

            if (difference > 0 && discountedPrice > 0) {
                return format === FORMATS.PERCENTAGE
                    ? {
                        text: Math.round((difference / price) * 100).toLocaleString(),
                        before: '-',
                        format: FORMATS.PERCENTAGE
                    }
                    : {
                        text: difference.toLocaleString(),
                        before: '-',
                        format: FORMATS.CURRENCY
                    };
            }

            if (difference < 0 && discountedPrice > 0) {
                return {
                    text: '판매 가격 확인 필요',
                    before: '',
                    format: ''
                };
            }

            return {
                text: '0',
                before: '',
                format: format === FORMATS.PERCENTAGE ? FORMATS.PERCENTAGE : FORMATS.CURRENCY
            };
        };

        // 할인 가격 계산
        const calculateDiscount = () => {
            const price = Number(elements.price.value) || 0;
            const discountedPrice = Number(elements.discountedPrice.value) || 0;
            const selectedFormat = Array.from(elements.formatRadios).find(radio => radio.checked)?.value || '-';

            const display = updateDiscountDisplay(price, discountedPrice, selectedFormat);

            elements.discountPrice.textContent = display.text;
            elements.discountPrice.dataset.before = display.before;
            elements.discountPrice.dataset.format = display.format;
        };

        // 입력값 validation
        const validateForm = () => {
            const company_id = Number(elements.company_id.value);
            const name = elements.name.value.trim();
            const price = Number(elements.price.value);
            const discountedPrice = Number(elements.discountedPrice.value);

            let isValid = true;
            const errors = {};

            if (!company_id) {
                errors.company_id = '업체를 선택해주세요';
                isValid = false;
            }
            if (!name) {
                errors.name = '상품명을 입력해주세요';
                isValid = false;
            }

            if (!price || price <= 0) {
                errors.price = '올바른 가격을 입력해주세요';
                isValid = false;
            }

            if (!discountedPrice) {
                errors.discountedPrice = '판매가격을 입력해주세요';
                isValid = false;
            }
            if (discountedPrice > price) {
                errors.discountedPrice = '판매가격은 원래 가격보다 클 수 없습니다';
                isValid = false;
            }

            return { isValid, errors };
        };

        const showError = (elementId, message) => {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'errors text-red-500 text-sm mt-1';
            errorDiv.textContent = message;
            elements[elementId].parentNode.appendChild(errorDiv);
        };

        const clearErrors = () => {
            elements.form.querySelectorAll('.errors').forEach(el => el.remove());
        };

        // 입력 이벤트리스너
        const addEventListeners = () => {
            elements.price.addEventListener('input', calculateDiscount);
            elements.discountedPrice.addEventListener('input', calculateDiscount);
            elements.formatRadios.forEach(radio =>
                radio.addEventListener('change', calculateDiscount)
            );

            elements.form.addEventListener('submit', (e) => {
                e.preventDefault();
                clearErrors();

                const { isValid, errors } = validateForm();

                if (!isValid) {
                    Object.keys(errors).forEach(key => {
                        showError(key, errors[key]);
                    });
                    return;
                }

                elements.form.submit();
            });
        };

        calculateDiscount();
        addEventListeners();
    });
</script>