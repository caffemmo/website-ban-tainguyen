# Provider contract checklist

Các adapter trong `app/Providers` chỉ là lớp kết nối server-side. Trước khi bật
thanh toán hoặc bán tự động, điền `.env` và kiểm tra từng contract thật.

## YouProxy

- Header: `X-API-Key: <server secret>`.
- Base URL và các path được cấu hình bằng `YOUPROXY_*_PATH`.
- Luồng tạo đơn lưu riêng `orderNumber` và `orderId`; không giả định hai giá trị này giống nhau.
- Nếu response chỉ có `orderNumber`, proxy vẫn được lưu để đối soát nhưng gia hạn cần
  `orderId` nội bộ được provider cấp sau đó.
- Không đưa provider payload thô cho khách; chỉ hiển thị thông tin kết nối đã được chuẩn hóa.

## Ngân hàng

- Adapter đọc `GET BANK_PROVIDER_TRANSACTIONS_PATH`.
- Header mặc định là `Authorization: Bearer <token>` và có thể đổi bằng env.
- Payload hỗ trợ danh sách ở `transactions`, `data` hoặc root array.
- Giao dịch chỉ được ghi có khi `type` là `IN`/`CREDIT`/`TRANSFER_IN`, có amount,
  external id và description chứa mã deposit `CFM...`.
- `transactionID` được dùng làm idempotency key; chạy cron lại không cộng trùng.

## Social provider

- `POST /api/getlink`, `/api/upfb`, `/api/upig` qua base URL cấu hình.
- Payload app chỉ gửi `link` hoặc `image_url` an toàn.
- Không triển khai luồng đăng nhập Facebook bằng UID/password/2FA/cookie.
- API key chỉ nằm trong environment và bị redact khỏi log.

## Kiểm thử bắt buộc

1. Test health endpoint.
2. Test provider bằng tài khoản sandbox hoặc amount nhỏ.
3. Test timeout, HTTP 4xx/5xx, JSON lỗi và provider trả `success: false`.
4. Chạy cron hai lần với cùng transaction và xác nhận số dư chỉ tăng một lần.
5. Kiểm tra refund khi provider proxy thất bại sau khi ví đã bị trừ.
