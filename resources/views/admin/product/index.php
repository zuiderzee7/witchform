<main class="container mx-auto">
    <div class="flex gap-4">
        <?php include BASE_PATH . "/resources/layouts/admin/aside.php"; ?>

        <section class="flex-1">
            상품 리스트
            <a href="/admin/product/form"
               class="bg-gray-600 hover:bg-green-700 text-white font-bold py-1 px-6 rounded-sm shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 inline-flex items-center">
                추가
            </a>

            <ul class="w-full">
                <!-- 헤더 -->
                <li class="grid grid-cols-6 gap-4 py-2 px-4 bg-gray-100 font-medium text-sm">
                    <div>상품명</div>
                    <div>회사명</div>
                    <div class="text-right">가격</div>
                    <div class="text-right">할인가격</div>
                    <div class="text-center">할인형식</div>
                    <div class="text-center">등록일</div>
                </li>

                <!-- 데이터 행 -->
                <li class="grid grid-cols-6 gap-4 py-3 px-4 border-b hover:bg-gray-50">
                    <div class="truncate"><?= htmlspecialchars($product['name']) ?></div>
                    <div class="truncate"><?= htmlspecialchars($product['company_name']) ?></div>
                    <div class="text-right"><?= number_format($product['price']) ?>원</div>
                    <div class="text-right">
                        <?= $product['discounted_price']
                            ? number_format($product['discounted_price']) . '원'
                            : '-' ?>
                    </div>
                    <div class="text-center">
                        <?= $product['discount_format'] ?? '-' ?>
                    </div>
                    <div class="text-center text-sm text-gray-600">
                        <?= date('Y-m-d', strtotime($product['created_dt'])) ?>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</main>