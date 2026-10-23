<?php

/**
 * Cấu hình nghiệp vụ của Mầm (quản lý Nhóm trẻ): loại ghi nhận nhật ký, bữa ăn, mức ăn,
 * điểm danh, danh mục kho, nhóm chi phí, tần suất lặp... Chỉnh ở đây là đổi toàn app.
 */
return [

    // Loại ghi nhận trong "Sổ nhật ký" của trẻ. fields: các ô nhập hiện ra khi chọn loại này.
    // item: có cho chọn vật tư trong kho (trừ kho theo SỐ LƯỢNG MỖI BÉ) hay không.
    // share: hiện cho phụ huynh xem ở trang phụ huynh / báo cáo ngày.
    'entry_types' => [
        'an' => ['label' => 'Ăn', 'icon' => 'fa-bowl-rice', 'color' => 'amber', 'fields' => ['meal', 'level'], 'item' => false, 'item_categories' => [], 'share' => true],
        'sua' => ['label' => 'Uống sữa', 'icon' => 'fa-bottle-water', 'color' => 'sky', 'fields' => ['amount_ml'], 'item' => true, 'item_categories' => ['sua'], 'share' => true],
        'ngu' => ['label' => 'Ngủ', 'icon' => 'fa-moon', 'color' => 'violet', 'fields' => ['sleep'], 'item' => false, 'item_categories' => [], 'share' => true],
        've_sinh' => ['label' => 'Vệ sinh / thay tã', 'icon' => 'fa-toilet-paper', 'color' => 'blue', 'fields' => ['toilet'], 'item' => true, 'item_categories' => ['ta_bim', 've_sinh'], 'share' => true],
        'thuoc' => ['label' => 'Uống thuốc', 'icon' => 'fa-pills', 'color' => 'rose', 'fields' => [], 'item' => true, 'item_categories' => ['thuoc'], 'share' => true],
        'suc_khoe' => ['label' => 'Đo nhiệt độ', 'icon' => 'fa-temperature-half', 'color' => 'orange', 'fields' => ['temperature'], 'item' => false, 'item_categories' => [], 'share' => true],
        'can_do' => ['label' => 'Cân đo', 'icon' => 'fa-ruler-vertical', 'color' => 'emerald', 'fields' => ['growth'], 'item' => false, 'item_categories' => [], 'share' => true],
        'hoat_dong' => ['label' => 'Hoạt động / học', 'icon' => 'fa-shapes', 'color' => 'emerald', 'fields' => [], 'item' => false, 'item_categories' => [], 'share' => true],
        'su_co' => ['label' => 'Sự cố / va chạm', 'icon' => 'fa-bandage', 'color' => 'red', 'fields' => [], 'item' => true, 'item_categories' => ['thuoc'], 'share' => true],
        'khac' => ['label' => 'Ghi chú', 'icon' => 'fa-note-sticky', 'color' => 'neutral', 'fields' => [], 'item' => true, 'item_categories' => [], 'share' => true],
    ],

    // Loại bắt buộc có ghi chú (để phụ huynh/chủ nhóm hiểu chuyện gì đã xảy ra).
    'note_required_types' => ['thuoc', 'su_co', 'hoat_dong', 'khac'],

    // Loại công việc (việc cần làm / lịch định kỳ). 'don_dep' là việc chung, không ghi vào sổ của trẻ.
    'task_types' => [
        'thuoc' => 'Cho bé uống thuốc',
        'an' => 'Cho ăn',
        'sua' => 'Cho uống sữa',
        'ngu' => 'Cho ngủ',
        've_sinh' => 'Vệ sinh / thay tã',
        'suc_khoe' => 'Đo nhiệt độ',
        'can_do' => 'Cân đo định kỳ',
        'hoat_dong' => 'Hoạt động / học',
        'don_dep' => 'Dọn dẹp / khử khuẩn',
        'khac' => 'Việc khác',
    ],
    'task_icons' => ['don_dep' => ['icon' => 'fa-spray-can-sparkles', 'color' => 'sky']],

    'meals' => [
        'sang' => 'Bữa sáng',
        'trua' => 'Bữa trưa',
        'xe' => 'Bữa xế',
        'phu' => 'Bữa phụ',
    ],

    'eat_levels' => [
        'het' => ['label' => 'Ăn hết', 'color' => 'emerald'],
        'gan_het' => ['label' => 'Gần hết', 'color' => 'sky'],
        'it' => ['label' => 'Ăn ít', 'color' => 'amber'],
        'khong' => ['label' => 'Không ăn', 'color' => 'red'],
    ],

    'toilet_kinds' => [
        'tieu' => 'Đi tiểu',
        'dai' => 'Đi nặng',
        'thay_ta' => 'Thay tã',
        'long' => 'Đi lỏng',
    ],

    'attendance_statuses' => [
        'co_mat' => ['label' => 'Có mặt', 'short' => 'Có', 'icon' => 'fa-circle-check', 'color' => 'emerald'],
        'vang_phep' => ['label' => 'Nghỉ có phép', 'short' => 'Phép', 'icon' => 'fa-envelope-open-text', 'color' => 'amber'],
        'vang' => ['label' => 'Nghỉ không phép', 'short' => 'Vắng', 'icon' => 'fa-circle-xmark', 'color' => 'red'],
    ],

    // Nhiệt độ từ mức này trở lên được coi là sốt (°C) — hiện cảnh báo đỏ.
    'fever_threshold' => 37.5,

    'item_categories' => [
        'thuc_pham' => ['label' => 'Thực phẩm', 'icon' => 'fa-carrot'],
        'sua' => ['label' => 'Sữa', 'icon' => 'fa-bottle-water'],
        'ta_bim' => ['label' => 'Tã bỉm', 'icon' => 'fa-baby'],
        've_sinh' => ['label' => 'Đồ vệ sinh', 'icon' => 'fa-soap'],
        'thuoc' => ['label' => 'Thuốc / y tế', 'icon' => 'fa-kit-medical'],
        'do_dung' => ['label' => 'Đồ dùng / học liệu', 'icon' => 'fa-puzzle-piece'],
        'khac' => ['label' => 'Khác', 'icon' => 'fa-cube'],
    ],

    'expense_categories' => [
        'thue_nha' => 'Thuê nhà / mặt bằng',
        'luong' => 'Lương cô / bảo mẫu',
        'di_cho' => 'Đi chợ / thực phẩm',
        'dien_nuoc' => 'Điện, nước, internet',
        'do_dung' => 'Đồ dùng, đồ chơi',
        'sua_chua' => 'Sửa chữa, bảo trì',
        'khac' => 'Khác',
    ],

    // Cách tính học phí. monthly: học phí cố định mỗi tháng. daily (gửi ngày): số ngày bé CÓ MẶT × đơn giá/ngày.
    // Tiền ăn luôn tính riêng = số ngày có mặt × tiền ăn/ngày (đặt tiền ăn = 0 nếu đơn giá ngày đã gồm tiền ăn).
    'tuition_modes' => [
        'monthly' => ['label' => 'Theo tháng', 'short' => 'Gửi tháng', 'hint' => 'Học phí cố định mỗi tháng'],
        'daily' => ['label' => 'Theo ngày', 'short' => 'Gửi ngày', 'hint' => 'Số ngày bé có mặt × đơn giá/ngày'],
    ],

    'payment_methods' => [
        'tien_mat' => 'Tiền mặt',
        'chuyen_khoan' => 'Chuyển khoản',
    ],

    'frequencies' => [
        'daily' => 'Mỗi ngày',
        'weekdays' => 'Ngày đi học (T2–T6)',
        'weekly' => 'Mỗi tuần',
        'monthly' => 'Mỗi tháng',
        'interval' => 'Cách N ngày',
    ],

    'weekday_labels' => [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'],

    // Số ngày tới mà công việc định kỳ được sinh sẵn (tạo lười khi mở app + lệnh mam:generate-tasks)
    'task_horizon_days' => 2,

    // Gợi ý lớp/nhóm theo độ tuổi (Thông tư 49/2021: nhóm trẻ nhận trẻ từ 3 đến 36 tháng tuổi).
    'classroom_presets' => [
        ['name' => 'Nhóm 3–12 tháng', 'age_label' => '3–12 tháng', 'capacity' => 12],
        ['name' => 'Nhóm 13–24 tháng', 'age_label' => '13–24 tháng', 'capacity' => 20],
        ['name' => 'Nhóm 25–36 tháng', 'age_label' => '25–36 tháng', 'capacity' => 25],
    ],

    'accent_colors' => ['pink' => 'Hồng', 'emerald' => 'Xanh lá', 'amber' => 'Vàng nghệ', 'orange' => 'Cam', 'blue' => 'Xanh dương', 'rose' => 'Đỏ hồng', 'violet' => 'Tím'],
];
