<nav class="mt-2 p-4 h-fit bg-white shadow-lg rounded-lg border-t border-solid border-gray-100" role="navigation">
    <ul class="space-y-2">
        <li class="text-lg font-bold px-3 py-1 bg-gray-100 rounded-md text-gray-800">메뉴</li>

        <li class="hover:bg-gray-50 rounded-md transition-colors duration-200">
            <a href="#" class="block px-4 py-2 text-gray-700 hover:text-blue-600">주문 리스트</a>
        </li>

        <li class="hover:bg-gray-50 rounded-md transition-colors duration-200">
            <a href="#" class="block px-4 py-2 text-gray-700 hover:text-blue-600">결제 리스트</a>
        </li>

        <li class="group relative hover:bg-gray-50 rounded-md transition-colors duration-200">
            <a href="/admin/company" class="block px-4 py-2 pr-2 text-gray-700 hover:text-blue-600 flex items-center justify-between">
                업체 리스트
                <svg class="w-4 h-4 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </a>
            <ul class="hidden group-hover:block absolute left-0 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                <li class="hover:bg-gray-50">
                    <a href="/admin/company/form" class="block px-4 py-2 text-sm text-gray-600 hover:text-blue-600">업체 추가</a>
                </li>
            </ul>
        </li>

        <li class="group relative hover:bg-gray-50 rounded-md transition-colors duration-200">
            <a href="/admin/product" class="block px-4 py-2 pr-2 text-gray-700 hover:text-blue-600 flex items-center justify-between">
                상품 리스트
                <svg class="w-4 h-4 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </a>
            <ul class="hidden group-hover:block absolute left-0 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                <li class="hover:bg-gray-50">
                    <a href="/admin/product/form" class="block px-4 py-2 text-sm text-gray-600 hover:text-blue-600">상품 추가</a>
                </li>
                <li class="hover:bg-gray-50">
                    <a href="/admin/product/inventory" class="block px-4 py-2 text-sm text-gray-600 hover:text-blue-600">재고 관리</a>
                </li>
            </ul>
        </li>

        <li class="group relative hover:bg-gray-50 rounded-md transition-colors duration-200">
            <a href="/admin/product" class="block px-4 py-2 pr-2 text-gray-700 hover:text-blue-600 flex items-center justify-between">
                게시물 리스트
                <svg class="w-4 h-4 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </a>
            <ul class="hidden group-hover:block absolute left-0 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                <li class="hover:bg-gray-50">
                    <a href="/admin/post/form" class="block px-4 py-2 text-sm text-gray-600 hover:text-blue-600">게시물 추가</a>
                </li>
                <li class="hover:bg-gray-50">
                    <a href="/admin/post/delivery" class="block px-4 py-2 text-sm text-gray-600 hover:text-blue-600">배송 설정</a>
                </li>
            </ul>
        </li>
    </ul>
</nav>
