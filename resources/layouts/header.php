<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$title ?? '파이어볼트-윗치폼'?></title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>
<body>
    <header class="bg-gray-200 p-4">
        <div class="container mx-auto flex justify-center sm:justify-start space-x-4">
            <a href="<?=$dir ?? '/'?>">
                <img src="https://d2i2w6ttft7yxi.cloudfront.net/ver2/common/logo.webp" class="w-30" alt="logo">
            </a>
            <a href="/admin" class="content-center hover:font-bold">
                관리자 가기
            </a>
        </div>
    </header>
