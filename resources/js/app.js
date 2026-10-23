import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * State + logic kéo-giãn chiều rộng cho các panel dạng sidebar (Sidebar quản lý,
 * panel Giỏ hàng...). Nhớ lại chiều rộng đã chọn qua localStorage, không cần
 * chọn lại mỗi lần mở app.
 *
 * direction: 'right'  = tay cầm nằm bên PHẢI panel, kéo sang phải để MỞ RỘNG
 *                        (dùng cho panel bên trái màn hình, VD: sidebar)
 *            'left'   = tay cầm nằm bên TRÁI panel, kéo sang trái để MỞ RỘNG
 *                        (dùng cho panel bên phải màn hình, VD: giỏ hàng)
 */
window.resizablePanel = function (storageKey, defaultWidth, min, max, direction = 'right') {
    return {
        width: parseInt(localStorage.getItem(storageKey)) || defaultWidth,
        resizing: false,

        startResize(e) {
            e.preventDefault();
            this.resizing = true;

            const getX = (ev) => (ev.touches ? ev.touches[0].clientX : ev.clientX);
            const startX = getX(e);
            const startWidth = this.width;

            const onMove = (ev) => {
                const delta = getX(ev) - startX;
                const signedDelta = direction === 'right' ? delta : -delta;
                this.width = Math.min(max, Math.max(min, startWidth + signedDelta));
            };

            const onEnd = () => {
                this.resizing = false;
                localStorage.setItem(storageKey, this.width);
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onEnd);
                document.removeEventListener('touchmove', onMove);
                document.removeEventListener('touchend', onEnd);
            };

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onEnd);
            document.addEventListener('touchmove', onMove, { passive: false });
            document.addEventListener('touchend', onEnd);
        },
    };
};

/**
 * Sao chép văn bản (báo cáo ngày, phiếu học phí, link phụ huynh) để dán vào Zalo.
 * navigator.clipboard CHỈ chạy trên HTTPS/localhost — trên domain http (VD: mam.test của Laragon)
 * phải dùng cách cũ: textarea ẩn + execCommand('copy').
 */
window.copyText = function (text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }

    return new Promise((resolve, reject) => {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.top = '-1000px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
        } catch (e) {
            reject(e);
        } finally {
            document.body.removeChild(ta);
        }
    });
};

Alpine.start();

/**
 * Tự động định dạng phân cách nghìn (kiểu Việt Nam: dấu chấm) cho các ô nhập
 * tiền VNĐ khi đang gõ — CHỈ ảnh hưởng hiển thị, giá trị gửi lên server khi
 * submit form luôn là số sạch không dấu chấm (xử lý ở sự kiện 'submit' của
 * form cha, KHÔNG cần đổi gì ở phía backend).
 *
 * Cách dùng: thêm class "money-input" + đổi type="number" thành type="text"
 * inputmode="numeric" cho input tiền VNĐ (số lớn, không có phần thập phân
 * lẻ) — KHÔNG dùng cho số lượng/định lượng có thể có số thập phân (VD: định
 * lượng nguyên liệu 0.5g) vì việc format sẽ làm mất phần thập phân đó.
 */
function initMoneyInputs() {
    document.querySelectorAll('.money-input').forEach((input) => {
        if (input.dataset.moneyFormatted) return; // tránh gắn listener 2 lần
        input.dataset.moneyFormatted = '1';

        const format = () => {
            const cursorFromEnd = input.value.length - input.selectionStart;
            const digits = input.value.replace(/\D/g, '');
            input.value = digits ? Number(digits).toLocaleString('vi-VN') : '';
            const newPos = Math.max(0, input.value.length - cursorFromEnd);
            input.setSelectionRange(newPos, newPos);
        };

        // Định dạng sẵn giá trị ban đầu (nếu form hiện giá trị cũ khi sửa).
        if (input.value) {
            input.value = Number(input.value.replace(/\D/g, '')).toLocaleString('vi-VN');
        }

        input.addEventListener('input', format);

        // Trước khi form submit, trả input này về số sạch (bỏ dấu chấm) để
        // server nhận đúng giá trị số — KHÔNG đổi input.value hiển thị mãi
        // mãi, chỉ đổi đúng khoảnh khắc ngay trước khi gửi đi.
        const form = input.closest('form');
        if (form && !form.dataset.moneyCleanupBound) {
            form.dataset.moneyCleanupBound = '1';
            form.addEventListener('submit', (e) => {
                // Người dùng bấm "Huỷ" ở hộp confirm() (onsubmit="return confirm(...)") → form KHÔNG gửi,
                // giữ nguyên số đang hiển thị có dấu chấm thay vì biến thành dãy số trần.
                if (e.defaultPrevented) return;
                form.querySelectorAll('.money-input').forEach((el) => {
                    el.value = el.value.replace(/\D/g, '');
                });
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', initMoneyInputs);
// Gọi lại sau khi Alpine render xong các khối động (x-show, x-for...) — các
// input tiền nằm trong đó chỉ thực sự có trong DOM sau khi Alpine khởi tạo.
document.addEventListener('alpine:initialized', initMoneyInputs);

/**
 * Chống bấm 2 lần (mạng 3G chậm, bấm "Có mặt"/"Xác nhận đã thu" liên tục): sau khi 1 form POST đã gửi đi,
 * các lần submit tiếp theo của CHÍNH form đó bị bỏ qua cho tới khi trang mới tải xong. Tự mở lại sau 8 giây
 * (phòng response là file tải về, trang không chuyển) và khi quay lại trang bằng nút Back (bfcache).
 * Form GET (bộ lọc) không bị ảnh hưởng. Thêm data-allow-resubmit vào <form> nếu cần bỏ qua.
 */
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (e.defaultPrevented || !(form instanceof HTMLFormElement)) return;
    if ((form.getAttribute('method') || 'get').toLowerCase() === 'get' || form.hasAttribute('data-allow-resubmit')) return;

    if (form.dataset.submitting) {
        e.preventDefault();
        return;
    }

    form.dataset.submitting = '1';
    setTimeout(() => delete form.dataset.submitting, 8000);
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting]').forEach((f) => delete f.dataset.submitting);
});
