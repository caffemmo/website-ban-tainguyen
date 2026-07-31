# Caffemmo Independent Platform

Nền tảng PHP/MySQL độc lập cho Caffemmo. Thư mục này không gọi license,
updater, callback hay endpoint nội bộ của source cũ. Source cũ chỉ được dùng
để đối chiếu nghiệp vụ và dữ liệu cần di chuyển.

## Đã có trong bản nền

- Đăng ký, đăng nhập, session cookie an toàn, password hash, CSRF và phân quyền admin.
- Dashboard, catalog, sản phẩm số, tồn kho, đơn hàng và lịch sử ví.
- Nạp tiền theo mã giao dịch riêng, cron đối soát, credit idempotent và log audit.
- Mua proxy, lưu order provider, danh sách proxy, định dạng kết nối và gia hạn.
- Workspace social cho Get Link Facebook, Up Facebook và Up Instagram bằng payload an toàn.
- Admin dashboard, bật bảo trì và chỉnh giá proxy mặc định.
- Provider adapter server-side cho YouProxy, ngân hàng và social provider.
- Docker, Apache rewrite, `.env`, log redact secret và giao diện responsive.
- Typography Be Vietnam Pro cho heading, Noto Sans cho nội dung; không dùng GIF hạt hoặc hiệu ứng nặng.

## Chạy local bằng Docker

```bash
copy .env.example .env
docker compose up --build
docker compose exec app php bin/create-admin.php "Caffemmo Admin" admin@example.com "ChangeThisPassword123!"
```

Mở `http://127.0.0.1:8080`.

## Cấu hình provider

1. Sao chép `.env.example` thành `.env`.
2. Điền database, `APP_SECRET` và `CRON_SECRET` bằng chuỗi ngẫu nhiên dài.
3. Điền API key provider ở server. Không đưa key vào JavaScript, HTML hoặc database public.
4. Đối chiếu path, header và payload trong `docs/provider-contracts.md` với tài liệu provider hiện tại.
5. Chạy health check trước khi mở bán:

```bash
curl http://127.0.0.1:8080/api/health
```

Adapter chỉ coi response thành công khi HTTP 2xx và provider không trả `success: false`
hoặc `status: false`.

## Chạy trên cPanel

1. Tạo database và user MySQL riêng cho platform mới.
2. Upload toàn bộ thư mục này, đặt document root vào `public`.
3. Copy `.env.example` thành `.env`, đặt quyền file `.env` chỉ cho user chạy PHP đọc được.
4. Chạy `database/schema.sql` một lần trên database mới.
5. Tạo admin bằng PHP CLI hoặc chạy script an toàn ngoài web root.
6. Tạo cron riêng cho platform, không dùng URL cron của source cũ:

```text
*/5 * * * * curl -fsS -H "X-Cron-Key: YOUR_CRON_SECRET" https://your-domain.example/cron.php >/dev/null
```

7. Kiểm tra `/api/health`, đăng nhập, tạo deposit intent và chạy một giao dịch test nhỏ.

## Dữ liệu cũ

Không bê nguyên source cũ vào bản mới. Cần map có chủ đích các bảng `users`,
`categories`, `products`, `product_stock`, `orders` và lịch sử ví; tuyệt đối
không copy license key, updater config hoặc các trường secret không cần thiết.

## Giới hạn an toàn

- Không có license check hay auto-update ghi đè source.
- Không nhận UID, mật khẩu, 2FA hoặc cookie Facebook/Instagram.
- Không ghi plaintext token, password, cookie hoặc API key vào log.
- API proxy/social chưa được coi là production-ready cho tới khi response mẫu,
  endpoint và quy tắc tính phí được xác nhận với nhà cung cấp thật.
