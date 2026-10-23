<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Nội dung 3 trang tĩnh lưu trong database để Super Admin tự sửa, không cần deploy lại.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('pages')->insert([
            [
                'slug' => 'gioi-thieu',
                'title' => 'Giới thiệu',
                'content' => '<p>Mầm là phần mềm quản lý dành cho <strong>nhóm trẻ gia đình, nhóm trẻ độc lập</strong> và các cô trông trẻ tại nhà — điểm danh, sổ nhật ký của bé (ăn, ngủ, vệ sinh, nhiệt độ, cân đo), thực đơn, công việc lặp lại, kho sữa/tã, học phí và chi phí ngay trên điện thoại.</p><p>Phụ huynh xem được nhật ký hằng ngày của con qua một đường link riêng do nhóm trẻ chia sẻ, không cần cài ứng dụng.</p><p>Nội dung trang này do quản trị viên nền tảng cập nhật.</p>',
                'meta_description' => 'Mầm — phần mềm quản lý nhóm trẻ: điểm danh, nhật ký bé, thực đơn, học phí, kho sữa tã. Đăng ký miễn phí, dùng ngay trên điện thoại.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'dieu-khoan-su-dung',
                'title' => 'Điều khoản sử dụng',
                'content' => '<p><em>Đây là bản khung tạm thời, cần được luật sư rà soát trước khi phát hành chính thức.</em></p><h3>1. Chấp nhận điều khoản</h3><p>Bằng việc đăng ký và sử dụng Mầm, bạn đồng ý tuân thủ các điều khoản được nêu tại trang này.</p><h3>2. Tài khoản người dùng</h3><p>Bạn chịu trách nhiệm bảo mật thông tin đăng nhập và mọi hoạt động diễn ra dưới tài khoản của mình, kể cả tài khoản của cô giáo/bảo mẫu do bạn tạo.</p><h3>3. Dữ liệu của trẻ</h3><p>Thông tin của trẻ em là dữ liệu cá nhân nhạy cảm. Chủ nhóm trẻ cam kết đã được cha mẹ hoặc người giám hộ đồng ý trước khi nhập thông tin của trẻ vào hệ thống và chỉ chia sẻ đường link phụ huynh cho đúng gia đình của trẻ.</p><h3>4. Giới hạn trách nhiệm</h3><p>Dịch vụ được cung cấp trên cơ sở "nguyên trạng". Mầm là công cụ ghi chép và hỗ trợ quản lý, không thay thế tư vấn y tế hay chuyên môn chăm sóc, giáo dục mầm non. Nhà cung cấp không chịu trách nhiệm cho các thiệt hại gián tiếp phát sinh từ việc sử dụng dịch vụ.</p><h3>5. Thay đổi điều khoản</h3><p>Điều khoản có thể được cập nhật theo thời gian. Việc tiếp tục sử dụng dịch vụ sau khi thay đổi đồng nghĩa với việc chấp nhận điều khoản mới.</p>',
                'meta_description' => 'Điều khoản sử dụng dịch vụ Mầm — quyền và trách nhiệm của bạn khi đăng ký, sử dụng phần mềm quản lý nhóm trẻ Mầm.',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'chinh-sach-bao-mat',
                'title' => 'Chính sách bảo mật',
                'content' => '<p><em>Đây là bản khung tạm thời, cần được luật sư rà soát trước khi phát hành chính thức.</em></p><h3>1. Thông tin thu thập</h3><p>Chúng tôi lưu thông tin bạn cung cấp khi đăng ký (tên, số điện thoại, tên nhóm trẻ) và dữ liệu phát sinh khi sử dụng: hồ sơ trẻ, thông tin liên hệ phụ huynh, điểm danh, nhật ký chăm sóc, học phí.</p><h3>2. Dữ liệu trẻ em</h3><p>Dữ liệu của trẻ chỉ được nhập khi đã có sự đồng ý của cha mẹ/người giám hộ, chỉ dùng cho việc chăm sóc trẻ và trao đổi với gia đình. Đường link phụ huynh có thể bị tắt hoặc đổi mới bất kỳ lúc nào.</p><h3>3. Chia sẻ thông tin</h3><p>Chúng tôi không bán hoặc cho thuê dữ liệu cá nhân của bạn và của trẻ cho bên thứ ba.</p><h3>4. Bảo mật dữ liệu</h3><p>Mật khẩu được mã hoá khi lưu trữ; mỗi nhóm trẻ chỉ xem được dữ liệu của chính mình.</p><h3>5. Quyền của bạn</h3><p>Bạn có quyền xuất hoặc xoá toàn bộ dữ liệu ngay trong phần Cài đặt / Hồ sơ cá nhân.</p>',
                'meta_description' => 'Chính sách bảo mật của Mầm — cách chúng tôi thu thập, sử dụng và bảo vệ dữ liệu của nhóm trẻ, của trẻ và phụ huynh.',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
    }
};
