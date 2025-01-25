<?php
// 세션이 시작되지 않았다면 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF 토큰 생성
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
        <section class="flex-1 p-4 sm:p-0">
            <div class="flex justify-between py-2">
                <h1 class="text-lg font-bold"><?= isset($post) ? '게시물 수정' : '게시물 추가' ?></h1>
                <div>
                    <a href="/admin/post"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1 px-6 rounded-sm shadow-sm
                              transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500
                              focus:ring-offset-2 inline-flex items-center">
                        목록
                    </a>
                </div>
            </div>

            <form id="postForm" action="/admin/post/api" method="POST" class="w-full max-w-4xl mx-auto">
                <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">

                <?php if(isset($post)): ?>
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                <?php endif; ?>

                <ul class="w-full divide-y divide-gray-200">
                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                제목 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="title" name="title"
                                   value="<?= isset($post) ? htmlspecialchars($post['title']) : '' ?>"
                                   required
                                   autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                                          focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </li>

                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label for="content" class="block text-sm font-medium text-gray-700">상세 설명</label>
                            <textarea id="content" name="content" rows="5"
                                      maxlength="1000"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            ><?= isset($post) ? htmlspecialchars($post['content']) : '' ?></textarea>
                        </div>
                    </li>

                    <li class="grid gap-4 py-4">
                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-700">배송 설정</label>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="pickup_cost" class="block text-sm text-gray-600">픽업 비용</label>
                                    <div class="price_format" data-format="원">
                                        <input type="number"
                                               id="pickup_cost"
                                               name="pickup_cost"
                                               value="<?= isset($post_delivery) ? (int)$post_delivery['pickup_cost'] : '' ?>"
                                               min="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="미입력 픽업 불가 or 0원 무료">
                                    </div>
                                </div>

                                <div>
                                    <label for="delivery_cost" class="block text-sm text-gray-600">기본 배송비</label>
                                    <div class="price_format" data-format="원">
                                        <input type="number"
                                                   id="delivery_cost"
                                                   name="delivery_cost"
                                                   value="<?= isset($post_delivery) ? (int)$post_delivery['delivery_cost'] : 3500 ?>"
                                                   min="0"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="3500">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="free_delivery_amount" class="block text-sm text-gray-600">무료배송 기준금액</label>
                                <div class="price_format" data-format="원">
                                    <input type="number"
                                               id="free_delivery_amount"
                                               name="free_delivery_amount"
                                               value="<?= isset($post_delivery) ? (int)$post_delivery['free_delivery_amount'] : '' ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="50000">
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label for="company_id" class="block text-sm font-medium text-gray-700">
                                업체 선택 <span class="text-red-500">*</span>
                            </label>
                            <select id="company_id" name="company_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    onchange="loadProducts(this.value)">
                                <option value="">업체를 선택하세요</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= (int) $company['id'] ?>"
                                        <?= isset($post) && $post['company_id'] == $company['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($company['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </li>

                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                관련 상품 <span class="text-red-500">*</span>
                            </label>
                            <div id="products-container" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <!-- 상품 목록이 여기에 동적 로드 -->
                            </div>
                        </div>
                    </li>


                    <?php if(!empty($_GET['error'])): ?>
                        <li>
                            <div class="font-bold">error.</div>
                            <div class="text-red-500 text-sm"><?= htmlspecialchars($_GET['error']) ?></div>
                        </li>
                    <?php endif; ?>

                    <li class="py-4 flex justify-end space-x-2">
                        <button type="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-sm shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <?= isset($post) ? '수정' : '추가' ?>
                        </button>
                    </li>
                </ul>
            </form>
        </section>
    </div>
</main>

<script>
    function loadProducts(companyId) {
        if (!companyId) {
            document.getElementById('products-container').innerHTML = '';
            return;
        }

        // API 호출하여 상품 목록 가져오기
        fetch(`/admin/company/api.php?id=${companyId}`)
            .then(response => response.json())
            .then(response => {
                const container = document.getElementById('products-container');
                if(response.products && response.products.length > 0){
                    container.innerHTML = response.products.map(product => `
                    <label class="flex items-center space-x-1">
                        <input type="checkbox"
                               name="product_ids[]"
                               value="${product.id}"
                               ${isProductChecked(product.id) ? 'checked' : ''}>
                        <span>${product.name}</span>
                    </label>
                `).join('');
                }else{
                    container.innerHTML = '상품 없음';
                }
            })
            .catch(error => {
                console.error('상품 목록 로드 실패:', error);
                alert('상품 목록을 불러오는데 실패했습니다.');
            });
    }

    // 이미 선택된 상품인지 확인
    function isProductChecked(productId) {
        const checkedProducts = <?= json_encode($checkedProductIds ?? []) ?>;
        return checkedProducts.includes(productId);
    }

    // 페이지 로드 시 업체가 이미 선택되어 있다면 상품 목록 로드
    document.addEventListener('DOMContentLoaded', function() {
        const companySelect = document.getElementById('company_id');
        if (companySelect.value) {
            loadProducts(companySelect.value);
        }
    });

    // 게시물 폼 유효성 검사
    document.getElementById('postForm').addEventListener('submit', function(e) {
        let hasError = false;

        // 일반 필수 필드 검사
        const requiredFields = ['title', 'company_id'];
        requiredFields.forEach(fieldId => {
            const input = document.getElementById(fieldId);
            if (!input.value.trim()) {
                hasError = true;
                input.classList.add('border-red-500');
            } else {
                input.classList.remove('border-red-500');
            }
        });

        // 체크박스 검사
        const checkedProducts = document.querySelectorAll('input[name="product_ids[]"]:checked');
        if (checkedProducts.length === 0) {
            hasError = true;
            // 체크박스 컨테이너에 에러 스타일 적용
            document.getElementById('products-container').classList.add('border', 'border-red-500', 'p-2', 'rounded');
        } else {
            document.getElementById('products-container').classList.remove('border', 'border-red-500', 'p-2', 'rounded');
        }

        if (hasError) {
            e.preventDefault();
            alert('필수 입력 항목을 모두 입력해주세요.');
        }
    });

</script>
