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
    <div class="flex flex-col sm:flex-row gap-4">
        <?php include BASE_PATH . "/resources/layouts/admin/aside.php"; ?>
        <section class="flex-1">
            <div class="flex justify-between py-2">
                <h1 class="text-lg font-bold"><?= isset($company) ? '업체 수정' : '업체 추가' ?></h1>
                <div>
                    <a href="/admin/company"
                       class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1 px-6 rounded-sm shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 inline-flex items-center">
                        목록
                    </a>
                </div>
            </div>

            <form id="companyForm" action="/admin/company/api" method="POST" class="w-full max-w-4xl mx-auto p-4">
                <!-- CSRF 토큰 전송 -->
                <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">

                <?php if(isset($company)): ?>
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="id" value="<?= $company['id'] ?>">
                <?php endif; ?>

                <ul class="w-full divide-y divide-gray-200">
                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                업체명 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   value="<?= isset($company) ? htmlspecialchars($company['name']) : '' ?>"
                                   required
                                   autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </li>

                    <li class="grid grid-cols-2 gap-4 py-4">
                        <div class="space-y-2">
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                이메일 <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email"
                                   value="<?= isset($company) ? htmlspecialchars($company['email']) : '' ?>"
                                   required
                                   autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="space-y-2">
                            <label for="contact" class="block text-sm font-medium text-gray-700">
                                연락처 <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="contact" name="contact"
                                   value="<?= isset($company) ? htmlspecialchars($company['contact']) : '' ?>"
                                   required
                                   autocomplete="off"
                                   placeholder="예: 010-1234-5678"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </li>

                    <li class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <label for="postal_code" class="block text-sm font-medium text-gray-700">우편번호</label>
                            <input type="text" id="postal_code" name="postal_code"
                                   value="<?= isset($company) ? htmlspecialchars($company['postal_code']) : '' ?>"
                                   autocomplete="off"
                                   maxlength="7"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="space-y-2">
                            <label for="address" class="block text-sm font-medium text-gray-700">
                                주소 <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="address" name="address"
                                   value="<?= isset($company) ? htmlspecialchars($company['address']) : '' ?>"
                                   required
                                   autocomplete="off"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
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
                            <?= isset($company) ? '수정' : '추가' ?>
                        </button>
                    </li>
                </ul>
            </form>
        </section>
    </div>
</main>

<script>
    // 클라이언트 측 필수 필드 검사
    document.getElementById('companyForm').addEventListener('submit', function(e) {
        const requiredFields = ['name', 'email', 'contact', 'address'];
        let hasError = false;

        requiredFields.forEach(field => {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                hasError = true;
                input.classList.add('border-red-500');
            } else {
                input.classList.remove('border-red-500');
            }
        });

        if (hasError) {
            e.preventDefault();
            alert('필수 입력 항목을 모두 입력해주세요.');
        }
    });

    // 연락처 입력 형식 검증
    document.getElementById('contact').addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9-]/g, '');
        if (value.length > 13) value = value.slice(0, 13);
        e.target.value = value;
    });

    // 우편번호 입력 형식 검증
    document.getElementById('postal_code').addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9-]/g, '');
        if (value.length > 7) value = value.slice(0, 7);
        e.target.value = value;
    });
</script>
