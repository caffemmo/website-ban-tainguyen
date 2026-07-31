# Feature map

Day la danh sach port nghiep vu, khong phai danh sach file copy tu source cu.

| Nhom source cu | Platform moi | Trang thai milestone |
|---|---|---|
| `home`, `product`, `products`, `categories`, `product-stock` | Catalog + kho | UI + schema |
| `product-order`, `product-orders`, `product-sold` | Orders + order items | Service + schema |
| `recharge-*`, `log-auto-bank`, `deposit_log` | Wallet deposits + wallet transactions + provider adapters | Service + schema |
| `proxy-buy`, `proxy-list`, `proxy-renew` | Proxy workspace + lifecycle | UI + schema + intent service |
| `up-tich-xanh`, social ajax | Social workspace + provider adapter | UI + adapter contract |
| `login`, `register`, `profile`, `security`, `change-password` | Auth + account security | Auth base |
| `admin home`, `users`, `roles`, `settings` | Admin dashboard + RBAC boundary | Dashboard + schema |
| `automations`, `cron/*`, `email_queue`, `telegram_queue` | Internal jobs + cron_runs + future queue workers | Cron base |
| `affiliate-*`, `coupons`, `promotions` | Affiliate/promotion bounded modules | Planned after core |
| `blog*`, `faq`, `document-api`, `contact` | Content/help workspace | Planned after core |
| `theme`, `language`, `translate-list` | Theme and locale settings | Planned after core |

## Dữ liệu migrate

Không chạy `INSERT ... SELECT *` giữa hai source. Mỗi bảng cần mapping rõ ràng,
backup trước khi migrate, dry-run row count, checksum các cột quan trọng và rollback.

Đặc biệt:

- Không migrate `license_key`, updater state hoặc endpoint licensing.
- Không migrate plaintext password/token/cookie vào audit log.
- Đơn cũ giữ nguyên mã tham chiếu, nhưng đơn mới dùng `order_code` riêng.
- Giao dịch ngân hàng cũ phải deduplicate theo transaction ID trước khi cộng số dư.
