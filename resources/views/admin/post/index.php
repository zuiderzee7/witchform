<main class="container mx-auto">
    <div class="flex gap-4">
        <?php include BASE_PATH . "/resources/layouts/admin/aside.php"; ?>

        <section class="flex-1">
            <div class="flex justify-between py-2">
                <h1 class="text-lg font-bold">게시글 목록</h1>
                <div>
                    <a href="/admin/post/form"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1 px-6 rounded-sm shadow-sm
                              transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500
                              focus:ring-offset-2 inline-flex items-center">
                        글쓰기
                    </a>
                </div>
            </div>

            <ul class="w-full divide-y divide-gray-200">
                <li class="grid grid-cols-4 gap-4 p-4 bg-gray-100 font-medium text-sm sticky top-0">
                    <div>ID</div>
                    <div>제목</div>
                    <div>상세설명</div>
                    <div class="text-center">관리</div>
                </li>

                <?php if(!empty($posts)): ?>
                    <?php foreach($posts as $post): ?>
                        <li class="grid grid-cols-4 gap-4 p-4 transition-colors hover:bg-gray-50">
                            <div>
                                <?= (int) $post['id'] ?>
                            </div>

                            <div class="truncate">
                                <a href="/admin/post/form?id=<?= (int) $post['id'] ?>"
                                   class="text-blue-500 hover:underline whitespace-nowrap"
                                   title="<?= htmlspecialchars($post['title']) ?>">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </div>
                            <div class="truncate">
                                <?= htmlspecialchars($post['content']) ?>
                            </div>

                            <div class="text-center space-x-px">
                                <button type="button"
                                        onclick="editPost(<?= (int) $post['id'] ?>)"
                                        class="px-2 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                    수정
                                </button>
                                <button type="button"
                                        onclick="deletePost(<?= (int) $post['id'] ?>)"
                                        class="px-2 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                    삭제
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="py-8 text-center text-gray-500">
                        등록된 글이 없습니다.
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center gap-2 mt-4">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=1" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">처음</a>
                        <a href="?page=<?= $current_page - 1 ?>" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">이전</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?page=<?= $i ?>"
                           class="px-3 py-1 rounded <?= $i == $current_page
                               ? 'bg-blue-500 text-white'
                               : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?= $current_page + 1 ?>" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">다음</a>
                        <a href="?page=<?= $total_pages ?>" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">마지막</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
    function editPost(id) {
        window.location.href = `/admin/post/form?id=${id}`;
    }

    function deletePost(id) {
        if (confirm('정말 삭제하시겠습니까?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/post/api.php'; // 실제 삭제 처리 경로

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;

            form.appendChild(methodInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
