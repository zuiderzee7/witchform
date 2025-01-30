# 파이어볼트

### 실행 및 테스트 방법
1. .env 등 설정 키값은 메일로 개별 전달
2. 기본 접근 페이지
3. 관리자 접근 페이지 (미인증)

### 기능
1. 게시물(상품), 주문&결제 리스트
2. 게시물 내 상품을 선택하여 주문 생성
   1. 주문 생성 시 재고 수정
3. 주문 리스트에서 결제하기(토스)
4. 결제 처리 : 완료, 실패 등
5. 결제 리스트 표출, 결제 취소하기
   1. 결제 취소 시 재고 수정

### Task Line
- 과제 체크 및 DB 기획 : 2025-01-22 18시 ~ 19시 (1시간)
- 서버 기획 : 2025-01-22 18시 50분 ~ 19시 (10분)
- 간소 비지니스 로직 기획 : 2025-01-22 19시 30분 ~ 19시 47분 (17분)
- 서버 세팅 : 2025-01-23 13시 30분 ~14시 5분 (35분)
- 개발 시간 : 
  - 2025-01-23 15시 ~ 17시, 19시 30분 ~ 22시 17분 (287분)
  - 2025-01-24 12시 ~ 13시 53분, 19시 ~ 19시 30분 (143분)
  - 2025-01-25 17시 ~ 18시 04분 (64분)
  - 2025-01-26 13시 ~ 16시 22분, 19시 50분 ~ 21시 (272분)
  - 2025-01-27 19시 35분 ~ 22시 (155분)
  - 2025-01-28 20시 ~ 22시 (120분)
  - 2025-01-30 10시 30분 ~ 13시 (150분)
  
- 1차 개발 완료 : 2025-01-30 11시 45분
- QA : 2025-01-30 11시 45분 ~ 13시
- 최종 개발 완료 : 2025-01-30 13시

### 기획 문서
<details><summary>디비 기획
</summary>

- 업체 Table : **companies**
    1. id
    2. 상호명 : name
    3. 이메일 : email
    4. 연락처 : contact
    5. 우편번호 : postal_code
    6. 주소 : address
    7. 등록 시간 : created_dt
    8. 수정 시간 : updated_dt
    - SQL Table

        ```sql
        CREATE TABLE companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            postal_code VARCHAR(10),
            address TEXT NOT NULL,
            created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        ```


---

- 상품 게시 Table : **posts**
    1. id
    2. 업체 id : company_id
    3. 제목 : title
    4. 상세 내용 : content
    5. 등록 시간 : created_dt
    6. 수정 시간 : updated_dt
    - SQL Table

        ```sql
        CREATE TABLE posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id)
        );
        ```

- 상품 Table : **products**
    1. id
    2. 업체 id : company_id
    3. 상품명 : name
    4. 가격 : price
    5. 할인된 가격 : discounted_price
    6. 할인 표출 형태 (- or %) : discount_format
    7. 등록 시간 : created_dt
    8. 수정 시간 : updated_dt
    - SQL Table

        ```sql
        CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            price INT NOT NULL,
            discounted_price INT,
            discount_format VARCHAR(10),
            created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id)
        );
        ```

    - 재고 Table : **product_inventories**
        1. id
        2. 상품 id
        3. 업체 id : company_id
        4. 총 재고 : total_inventory
        5. 현재 재고 : current_inventory
        6. 등록 시간 : created_dt
        7. 수정 시간 : updated_dt
        - SQL Table

            ```sql
            CREATE TABLE product_inventories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                company_id INT NOT NULL,
                total_inventory INT NOT NULL DEFAULT 0,
                current_inventory INT NOT NULL DEFAULT 0,
                created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id),
                FOREIGN KEY (company_id) REFERENCES companies(id)
            );
            ```

- 게시 ↔ 상품 연관 Table : **post_products**
    1. id
    2. 업체 id : company_id
    3. 게시 id : post_id
    4. 상품 id : product_id
    5. post 내 판매 가능 최대 상품 수 (재고와 별개) : posted_inventory
    6. 등록 시간 : created_dt
    7. 수정 시간 : updated_dt
    - SQL Table

        ```sql
        CREATE TABLE post_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            post_id INT NOT NULL,
            product_id INT NOT NULL,
            posted_inventory INT NOT NULL DEFAULT 0,
            created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id),
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        );
        ```

- 게시 ↔ 배송 연관 Table: **post_delivery**
    1. id
    2. 업체 id : company_id
    3. 게시 id : post_id
    4. 현장 수령 비용 : pickup_cost : null의 경우 사용 불가
    5. 택배 배송 비용 : delivery_cost : null의 경우 사용 불가
    6. 특정 결제 금액부터의 배송 비용 : free_delivery_amount : null의 경우 사용 불가
    7. 이외 배송 방법 : extra_delivery_method  : null의 경우 사용 불가
    8. 이외 배송 방법 비용 :  extra_delivery_cost  : null의 경우 사용 불가
    9. 등록 시간 : created_dt
    10. 수정 시간 : updated_dt
    - SQL Table

        ```sql
        CREATE TABLE post_delivery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        post_id INT NOT NULL,
        pickup_cost INT DEFAULT NULL,
        delivery_cost INT DEFAULT 3500,
        free_delivery_amount INT DEFAULT NULL,
        extra_delivery_method VARCHAR(50),
        extra_delivery_cost INT DEFAULT NULL,
        created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id),
        FOREIGN KEY (post_id) REFERENCES posts(id)
        );
        ```


---

- 주문 Table : **orders**
    1. id
    2. 업체 id : company_id
    3. 게시 id : post_id
    4. 주문 번호 : order_number : 년월일His+user_id(더미값)+랜덤숫자(4자)
    5. 구매자명 : customer_name
    6. 구매자 이메일 : customer_email
    7. 구매자 연락처 : customer_phone
    8. 배송 방법 : delivery_type
    9. 배송비 : delivery_cost
    10. 우편번호 : postal_code
    11. 주소 : address
    12. 총 결제 금액 : total_amount
    13. 주문 상태 : status (ENUM - pending, paid, cancelled, completed)
    14. 등록 시간 : created_dt
    15. 수정 시간 : updated_dt
    - SQL Table

        ```sql
        CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            post_id INT NOT NULL,
            order_number VARCHAR(20) NOT NULL,
            customer_name VARCHAR(50) NOT NULL,
            customer_email VARCHAR(100),
            customer_phone VARCHAR(20) NOT NULL,
            delivery_type VARCHAR(20) NOT NULL,
            delivery_cost INT DEFAULT 0,
            postal_code VARCHAR(10),
            address TEXT,
            total_amount INT NOT NULL,
            order_status ENUM('pending', 'paid', 'cancelled', 'completed') DEFAULT 'pending',
            created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id),
            FOREIGN KEY (post_id) REFERENCES posts(id)
        );
        ```

- 주문 ↔ 상품 연관 Table: **order_products**
    1. id
    2. 게시 id : post_id
    3. 주문 id : order_id
    4. 상품 id : product_id
    5. 수량 : quantity
    6. 구매 당시 가격 : price
    7. 등록 시간 : created_dt
    - SQL Table

        ```sql
        CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price INT NOT NULL,
            created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        );
        ```

- 결제 정보 Table: **payments**
    1. id : 토스 페이먼트 결제 키값으로 활용
    2. 주문 id : order_id
    3. 결제 관련 정보 저장 : mid
    4. 결제 방식 : payment_type
    5. 결제 금액 : amount
    6. 취소 금액 : cancelled_amount
    7. tax 금액 : tax_amount
    8. 결제 상태 : status (ENUM - ready, paid, cancelled, failed)
    9. 결제 완료 시간 : paid_at
    10. 결제 취소 시간 : cancelled_at
    11. 취소 사유 : cancel_reason
    12. 실패 사유 : fail_reason
    13. 등록 시간 : created_dt
    14. 수정 시간 : updated_dt
    - SQL Table

        ```sql
        
        CREATE TABLE payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            mid VARCHAR(100) DEFAULT NULL COMMENT '결제 관련 id',
            payment_type VARCHAR(50) NOT NULL,
            amount INT NOT NULL,
            cancelled_amount INT DEFAULT NULL,
            tax_amount INT DEFAULT NULL,
            status ENUM('ready', 'paid', 'cancelled', 'failed') DEFAULT 'ready',
            paid_at DATETIME DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            cancel_reason TEXT DEFAULT NULL,
            fail_reason TEXT DEFAULT NULL,
            created_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        );
        ```

    - 이외 카드 정보등 미저장
</details>



<details><summary>비지니스 로직 정리
</summary>

1. 상품 목록 페이지
    1. posts와 연관 products 정보를 간소하게 표출
        1. 상품명
        2. 재고
        3. 개당 금액
    2. 선택하여 상세 접근
    3. 품절 표기
2. 상품 상세 페이지
    1. posts 정보 표출
    2. products 리스트 표출
        1. 상품 선택 및 요금 계산
        2. 주문하기 처리
3. 주문 처리 페이지
    1. 주문 처리
        1. 재고 감소
    2. 주문 내용 표출
    3. 결제 방법 선택
    4. 결제하기 처리
4. 토스 페이먼츠 결제 처리
    1. 토스 api 활용
5. 결제 결과 페이지
    1. 실패 시 재고 증가
6. 주문 리스트 페이지(전체 표출)
    1. 주문, 결제 리스트 및 데이터 표출
        1. 상태 표시
        2. 결제, 주문 정보 표출
    2. 결제 취소하기 기능 처리
</details>


<details><summary>서버 구축 기획
</summary>

- AWS 사용
    - Amazon linux2 AMI 사용
        - t2.micro 프리티어

        ```bash
        # 시스템 업데이트
        sudo yum update -y
        
        # PHP 7.2 설치
        sudo amazon-linux-extras install -y php7.2
        
        # PHP 필수 모듈 설치
        sudo yum install php php-cli php-common php-dba php-gd php-json php-mbstring php-mysqlnd php-opcache php-xml php-soap
        ```

    - RDS
        - mysql 8.0 사용
            - db.t2.micro 사용
    - S3 (미사용)
        - 상품 이미지 필요 시 저장
</details>





### 과제 : 주문 및 결제 프로세스 구현
다음과 같은 판매정보를 바탕으로, 구매자가 상품을 확인하고 주문 및 결제를 진행할 수 있는 주문/결제 프로세스를 구성해 주세요.

---
### 판매 정보
##### **판매자 정보**
- 상호: 주식회사 파이어볼트
- 이메일: cs@witchform.com
- 연락처: 070-4353-2888
- 주소: 04031 서울특별시 마포구 동교로 162-10, 2층

##### **상품 정보**
- 제목: 윗치폼 오리지널 인형 판매
- 상세내용: 그동안 윗치폼에서 제작한 인형들의 재고를 저렴하게 처분하고자 합니다.
- 상품 리스트:
    - **푸딩햄** (15,000원 / 재고수량 10개)
    - **쥬니햄** (15,000원 / 재고수량 10개)
    - **고양이 히나쿠우** (15,000원 / 재고수량 10개)
    - **너구리 히나쿠우** (15,000원 / 재고수량 10개)

##### **배송 방법**
- 현장수령 (0원)
- 택배 (3,500원)
---

### **요구 기능**

1. **상품 정보 확인**
    - 상품의 상세 정보와 **남은 재고 수량**을 확인할 수 있어야 합니다.
2. **주문 신청서 작성 및 확인**
    - 구매자 입장에서 **주문 신청서**를 작성하고, 아래 항목들을 확인할 수 있어야 합니다.
        - 구매 정보 (구매한 상품, 수량 등)
        - 구매자 정보 (이름, 연락처, 배송지 등)
        - 결제 정보 (총 결제 금액, 배송비 포함)
        - 주문 신청서 상태 정보 (결제 대기, 결제 완료 등)
3. **결제 기능**
    - **토스페이먼츠 API**를 활용하여 주문 신청서를 결제할 수 있어야 합니다.
4. **결제 취소 기능**
    - 이미 결제된 주문 신청서를 **결제 취소**할 수 있어야 합니다.

---

### **과제 조건**

1. **기술 스택**
    - 백엔드: 반드시 **순수 PHP**로 작성해 주세요. (현재 사용중인 버전은 7.2입니다)
        - DB는 mysql 8.0 버전을 기준으로 합니다.
    - 프론트엔드: 가능하면 **React**를 사용하여 작성해 주세요.
        - React를 사용하지 못할 경우, **HTML + JavaScript**로 구현해도 괜찮습니다.
2. **디자인(UI/UX)**
    - 과제는 기능 구현이 중심입니다. **디자인은 평가 요소에 포함되지 않으므로 단순한 형태로 작성**하셔도 괜찮습니다.
3. **결제 관련 작업**
    - **PG 결제**는 **토스페이먼츠**의 테스트 API를 활용해 구현해 주세요.
    - 테스트 API 키는 [토스페이먼츠 개발자센터](https://developers.tosspayments.com/)에서 회원가입 후 발급받을 수 있습니다.

---

### **과제 제출 방법**

1. **과제 결과물**:
    - 코드와 결과물 확인이 가능한 URL을 제공해 주세요.
    - 제출 시, 간단한 실행 및 테스트 방법을 README 파일에 작성해 주세요.
2. **제출 기한**:
    - 희망 면접일 **2일 전까지** 결과물을 제출해 주세요.

---

### **추가 평가 요소**

- **코드의 구조 및 가독성**: 명확하고 효율적인 코드 작성.
- **기능 구현의 완성도**: 요구된 기능이 정상적으로 작동하는지.
- **테스트 및 안정성**: 기본적인 예외 상황을 고려했는지.
- **실제 서비스 수준의 백엔드 개발 관점**: 프로덕션 환경에서도 사용할 수 있는 코드 설계.