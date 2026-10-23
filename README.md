# Mầm — Phần mềm quản lý Nhóm trẻ (tự host, multi-tenant)

Chăm bé mỗi ngày, bố mẹ yên tâm. Dành cho **nhóm trẻ gia đình / nhóm trẻ độc lập** (trẻ 3–36 tháng, vài bé đến vài chục bé) — KHÔNG phải phần mềm trường mầm non. Xây trên cùng nền Laravel với Chăm và Rót.

## Nghiệp vụ
- **Điểm danh**: có mặt / nghỉ có phép / nghỉ không phép, giờ đến (tự ghi), giờ về + người đón, nút "các bé còn lại đều có mặt".
- **Sổ nhật ký bé**: ăn (bữa + mức ăn), uống sữa (ml), ngủ (từ–đến, tự tính phút), vệ sinh/thay tã, uống thuốc, nhiệt độ (≥ 37,5°C = sốt), cân đo, hoạt động, sự cố. Ghi cho nhiều bé một lần; có dùng đồ trong kho (sữa, tã…) thì tự trừ kho theo **số lượng mỗi bé**.
- **Báo cáo ngày** gửi phụ huynh: mỗi bé 1 đoạn văn bản, bấm "Sao chép" rồi dán Zalo.
- **Link phụ huynh** `/phu-huynh/{token}`: không cần đăng nhập, xem nhật ký 7 ngày, điểm danh, cân đo, thực đơn tuần, học phí còn nợ + mã VietQR. Chủ nhóm bật/đổi/tắt link ở hồ sơ bé; bé nghỉ học tự tắt link.
- **Công việc định kỳ** (uống thuốc theo giờ, khử khuẩn đồ chơi, cân đo hằng tháng…): bấm "Xong" là ghi nhật ký cho các bé **đang có mặt** (bỏ qua bé vắng).
- **Thực đơn tuần**, chép tuần trước. **Kho** sữa/tã/đồ dùng (giá vốn bình quân, cảnh báo sắp hết).
- **Học phí**: học phí tháng (mức chung hoặc riêng từng bé) + tiền ăn = số ngày có mặt × tiền ăn/ngày + khoản khác − giảm trừ; thu nhiều lần; phiếu báo dạng tin nhắn + mã VietQR (nội dung chuyển khoản tuỳ chỉnh, mặc định `HP 2610 TEN BE`, để trống nếu muốn ngân hàng tự điền); công nợ xuất CSV.
- **Báo cáo thu/chi** theo tháng (tiền thực thu, thực chi + nhập kho), chuyên cần, sĩ số.
- **Hôm nay**: sĩ số, cảnh báo sốt/sự cố/bỏ ăn, dị ứng của bé có mặt, sinh nhật, thực đơn, việc cần làm, học phí chưa thu.
- **Phân quyền**: Super Admin (đồng thời là chủ nhóm đầu tiên, dùng được mọi tính năng như tài khoản thường; ngoài ra xem số liệu tổng và **đóng/mở đăng ký công khai** tại `/sieu-quan-tri/cai-dat`) · Chủ nhóm (toàn quyền, nhiều cơ sở) · Cô giáo (điểm danh, nhật ký, việc, xem hồ sơ bé; không thấy tiền).

Tham khảo quy định: Thông tư 49/2021/TT-BGDĐT (nhóm trẻ độc lập nhận trẻ 3–36 tháng; nhóm ≤ 7 trẻ: 1 người trông tối đa 2 trẻ 3–12 tháng hoặc 3 trẻ 12–36 tháng; nhóm lớn: tối đa 12 / 20 / 25 trẻ theo độ tuổi).

## Chạy local
```bash
composer install
npm install && npm run build
cp .env.example .env              # sửa DB_*
php artisan key:generate
php artisan migrate
php artisan serve                 # mở /install để tạo Super Admin (cũng là chủ nhóm đầu tiên)
```
Production: document root = `public/`, `SESSION_SECURE_COOKIE=true` khi có HTTPS, cron `* * * * * php artisan schedule:run` (không bắt buộc — việc định kỳ tự sinh khi mở app).

## Kiểm thử
`vendor/bin/phpunit` (SQLite) hoặc MySQL: `DB_CONNECTION=mysql DB_DATABASE=mam_test DB_USERNAME=... DB_PASSWORD=... vendor/bin/phpunit`.
`tests/Feature/MamFlowTest.php` chạy luồng điểm danh → nhật ký → việc định kỳ → học phí → trang phụ huynh, và quét mọi trang GET với vai trò chủ nhóm / cô giáo / Super Admin.

## Cấu hình
`config/mam.php`: loại nhật ký, bữa ăn, mức ăn, điểm danh, ngưỡng sốt, nhóm kho, nhóm chi phí, gợi ý lớp theo độ tuổi.
