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

<main class="container mx-auto">
    <h1 class="text-xl font-bold mb-4"><?= isset($post) ? '게시글 수정' : '게시글 등록' ?></h1>

    <form action="/admin/post/form_process.php" method="POST">
        <input type="hidden" name="company_id" value="1" />

        <?php if(isset($post)): ?>
            <input type="hidden" name="id" value="<?= $post['id'] ?>" />
        <?php endif; ?>

        <div class="mb-4">
            <label for="title" class="block mb-1">제목</label>
            <input type="text" id="title" name="title" required
                   value="<?= isset($post) ? htmlspecialchars($post['title']) : '' ?>"
                   class="border w-full px-2 py-1">
        </div>

        <div class="mb-4">
            <label for="content" class="block mb-1">내용</label>
            <textarea id="content" name="content" rows="5" class="border w-full px-2 py-1"><?= isset($post) ? htmlspecialchars($post['content']) : '' ?></textarea>
        </div>

        <div class="mb-4">
            <label class="block mb-1">관련 상품</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <?php foreach ($products as $product): ?>
                    <label class="flex items-center space-x-1">
                        <input type="checkbox" name="product_ids[]" value="<?= $product['id'] ?>"
                            <?= in_array($product['id'], $checkedProductIds) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($product['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                <?= isset($post) ? '수정하기' : '등록하기' ?>
            </button>
        </div>
    </form>
</main>
