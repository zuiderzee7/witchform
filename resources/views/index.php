<main class="container mx-auto p-4 divide-y divide-y-black">
    <!-- 게시물 리스트 -->
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
                                <?= htmlspecialchars($post['company_name']) ?>
                            </p>
                            <p class="font-medium text-gray-600">
                                <?= htmlspecialchars($post['title']) ?>
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

    <!-- 주문 리스트 -->
    <section class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">주문 리스트 (최근5개)</h2>
        </div>

        <ul class="divide-y divide-gray-200">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <li class="py-4 flex justify-between items-center">
                        <div class="flex-1">
                            <p class="font-medium">주문번호: <?= htmlspecialchars($order['order_number']) ?></p>
                            <p class="text-sm text-gray-500">
                                <?= (new DateTime($order['created_dt']))
                                    ->setTimezone(new DateTimeZone('Asia/Seoul'))
                                    ->format('Y-m-d H:i:s')
                                ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="py-4 text-center text-gray-500">주문 내역이 없습니다.</li>
            <?php endif; ?>
        </ul>
    </section>

    <!-- 결제 리스트 -->
    <section class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">결제 리스트 (최근5개)</h2>
        </div>

        <ul class="divide-y divide-gray-200">
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $payment): ?>
                    <li class="py-4 flex justify-between items-center">
                        <div class="flex-1">
                            <p class="font-medium">결제번호: <?= htmlspecialchars($payment['payment_number']) ?></p>
                            <p class="text-sm text-gray-500">
                                <?= (new DateTime($payment['created_dt']))
                                    ->setTimezone(new DateTimeZone('Asia/Seoul'))
                                    ->format('Y-m-d H:i:s')
                                ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="py-4 text-center text-gray-500">결제 내역이 없습니다.</li>
            <?php endif; ?>
        </ul>
    </section>
</main>
