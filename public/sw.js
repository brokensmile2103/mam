// Cố tình KHÔNG cache request nào — Mầm là app quản lý nhóm trẻ, dữ liệu (điểm
// danh, nhật ký, học phí, việc cần làm...) luôn phải mới nhất, hiện dữ liệu cũ từ cache
// có thể khiến ghi nhận sai số lượng hoặc bỏ sót việc. Service worker
// này chỉ tồn tại để trình duyệt (đặc biệt Android/Chrome) cho phép "Cài đặt
// ứng dụng" lên màn hình chính — yêu cầu bắt buộc phải có sẵn 1 fetch handler
// nào đó, dù không làm gì thêm ngoài chuyển tiếp y nguyên ra mạng.
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});
