{{-- Hộp báo lỗi kiểm tra dữ liệu cho các trang KHÔNG có ô nhập riêng hiển thị lỗi (điểm danh, danh sách học phí, thực đơn...) --
     trước đây lỗi (VD: thu quá số còn nợ, giờ về trước giờ đến) bị nuốt mất, người dùng tưởng đã lưu. --}}
@if($errors->any())
    <div {{ $attributes->merge(['class' => 'bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm']) }} role="alert">
        <ul class="space-y-0.5">@foreach($errors->all() as $error)<li><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
