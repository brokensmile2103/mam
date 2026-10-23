<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $seoTitle = 'Mầm — Sổ điểm danh, nhật ký bé và học phí cho nhóm trẻ gia đình';
        $seoDescription = 'Mầm giúp nhóm trẻ gia đình, nhóm trẻ độc lập điểm danh, ghi nhật ký bé (ăn, ngủ, vệ sinh, nhiệt độ), gửi báo cáo ngày cho phụ huynh và thu học phí theo tháng hoặc theo ngày có mã VietQR. Dùng ngay trên điện thoại, đăng ký miễn phí.';
        $seoImage = asset('og-image.png');
        $seoUrl = url()->current();
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $seoUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Mầm">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="theme-color" content="#db2777">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300..700&family=Mali:wght@500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /*
         * Mầm — trang giới thiệu. Ý tưởng: "vở ô ly + mực tím" — cuốn sổ liên lạc quen thuộc của mọi
         * cô giáo Việt Nam, nay là màn hình điện thoại. Lexend cho chữ đọc, Mali (chữ viết tay) CHỈ dùng
         * cho những gì cô "viết" vào sổ. Hồng Mầm chỉ dành cho nút hành động.
         */
        .m-page {
            --ink: #4b2e83;          /* mực tím */
            --ink-soft: #7a64a8;
            --text: #2a2438;
            --muted: #6b6480;
            --line: #e7e1f3;
            --grid: #cfc3ea;
            --grid-fine: #efeaf8;
            --margin: #f0a3b5;
            --brand: #db2777;        /* hồng Mầm — trùng màu trong app */
            --brand-dark: #be185d;
            --paper: #ffffff;
            --wash: #f8f6fc;
            font-family: 'Lexend', ui-sans-serif, system-ui, sans-serif;
            color: var(--text);
            background: var(--paper);
            font-size: 1rem;
            line-height: 1.6;
            font-weight: 350;
        }
        .m-page *:focus-visible { outline: 3px solid var(--brand); outline-offset: 3px; border-radius: 6px; }
        .m-hand { font-family: 'Mali', 'Lexend', cursive; color: var(--ink); }
        .m-wrap { max-width: 1120px; margin: 0 auto; padding: 0 20px; }

        /* Header */
        .m-head { display: flex; align-items: center; gap: 12px; height: 72px; }
        .m-logo { display: inline-flex; align-items: center; gap: 10px; color: var(--ink); text-decoration: none; }
        .m-logo i { width: 38px; height: 38px; border-radius: 12px; background: var(--brand); color: #fff; display: grid; place-items: center; font-size: 1.05rem; }
        .m-logo b { font-size: 1.5rem; font-weight: 600; letter-spacing: -0.02em; }
        .m-head nav { margin-left: auto; display: flex; align-items: center; gap: 22px; font-size: .95rem; white-space: nowrap; }
        @media (max-width: 420px) { .m-head nav { gap: 14px; font-size: .88rem; } .m-logo b { font-size: 1.3rem; } }
        .m-head nav a { color: var(--muted); text-decoration: none; }
        .m-head nav a:hover { color: var(--ink); }
        .m-head .m-nav-hide { display: none; }
        @media (min-width: 720px) { .m-head .m-nav-hide { display: inline; } }

        .m-btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 24px; border-radius: 14px; font-weight: 500; text-decoration: none; transition: background-color .15s, color .15s; }
        .m-btn-primary { background: var(--brand); color: #fff !important; }
        .m-btn-primary:hover { background: var(--brand-dark); }
        .m-btn-ghost { color: var(--ink) !important; padding-left: 6px; padding-right: 6px; }
        .m-btn-ghost:hover { text-decoration: underline; text-underline-offset: 4px; }
        .m-btn-sm { padding: 9px 16px; border-radius: 11px; font-size: .9rem; }

        /* Hero */
        .m-hero { display: grid; gap: 48px; padding-top: 32px; padding-bottom: 72px; align-items: center; }
        @media (min-width: 960px) { .m-hero { grid-template-columns: 1fr 1.05fr; gap: 64px; padding-top: 56px; padding-bottom: 104px; } }
        .m-hero h1 { font-size: clamp(2.2rem, 4.6vw, 3.3rem); line-height: 1.12; font-weight: 600; letter-spacing: -0.03em; color: var(--ink); max-width: 17ch; text-wrap: balance; }
        .m-lede { margin-top: 22px; font-size: 1.15rem; color: var(--muted); max-width: 46ch; }
        .m-cta { margin-top: 32px; display: flex; flex-wrap: wrap; align-items: center; gap: 12px 18px; }
        .m-fine { margin-top: 18px; font-size: .88rem; color: var(--muted); max-width: 46ch; }

        /* Trang vở ô ly: ô lớn 32px, mỗi ô 4 dòng kẻ mảnh, lề đỏ bên trái. */
        .m-vo {
            position: relative;
            background-color: var(--paper);
            background-image:
                linear-gradient(90deg, transparent 55px, var(--margin) 55px, var(--margin) 57px, transparent 57px),
                linear-gradient(var(--grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid) 1px, transparent 1px),
                linear-gradient(var(--grid-fine) 1px, transparent 1px);
            background-size: 100% 100%, 32px 32px, 32px 32px, 8px 8px;
            border-radius: 6px 18px 18px 6px;
            box-shadow: 0 1px 0 var(--line), 0 24px 48px -28px rgba(75, 46, 131, .45);
            border: 1px solid var(--line);
            padding: 31px 22px 32px 72px;
        }
        .m-vo::before { /* gáy vở */
            content: ''; position: absolute; left: -1px; top: 18px; bottom: 18px; width: 10px;
            background: radial-gradient(circle at 5px 8px, #d9d2ea 3px, transparent 3.5px) 0 0 / 10px 32px repeat-y;
        }
        .m-vo .m-hand { font-size: 1.08rem; line-height: 32px; }
        .m-vo-title { font-weight: 600; }
        .m-vo-row { display: grid; grid-template-columns: 92px 1fr; }
        .m-vo-row span:first-child { color: var(--ink-soft); }
        .m-vo-alert { color: #c2255c; }
        .m-vo-stamp {
            position: absolute; right: 18px; bottom: -18px; transform: rotate(-4deg);
            background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 10px 14px;
            font-size: .82rem; color: var(--text); display: flex; align-items: center; gap: 10px;
            box-shadow: 0 12px 28px -16px rgba(42, 36, 56, .5);
        }
        .m-vo-stamp i { color: #2f9e44; font-size: 1.1rem; }
        @media (max-width: 520px) {
            .m-vo { padding-left: 64px; padding-right: 14px; }
            .m-vo .m-hand { font-size: .95rem; }
            .m-vo-row { grid-template-columns: 58px 1fr; }
        }

        /* Section chung */
        .m-section { padding: 64px 0; }
        @media (min-width: 768px) { .m-section { padding: 96px 0; } }
        @media (max-width: 480px) { .m-slip { padding: 18px; } }
        .m-wash { background: var(--wash); }
        .m-h2 { font-size: clamp(1.75rem, 3.4vw, 2.5rem); line-height: 1.2; font-weight: 600; letter-spacing: -0.025em; color: var(--ink); max-width: 22ch; }
        .m-sub { margin-top: 14px; color: var(--muted); font-size: 1.08rem; max-width: 56ch; }

        /* Dòng thời gian một ngày */
        .m-day-layout { display: grid; gap: 48px; }
        @media (min-width: 960px) { .m-day-layout { grid-template-columns: 0.9fr 1.3fr; gap: 64px; } .m-day-intro { position: sticky; top: 40px; align-self: start; } }
        .m-day { display: grid; gap: 0; }
        .m-stop { display: grid; grid-template-columns: 96px 28px 1fr; column-gap: 12px; }
        .m-stop time { font-family: 'Mali', cursive; color: var(--ink); font-size: 1.35rem; line-height: 1.2; padding-top: 2px; text-align: right; }
        .m-rail { position: relative; }
        .m-rail::before { content: ''; position: absolute; left: 13px; top: 0; bottom: 0; width: 2px; background: var(--grid); }
        .m-stop:first-child .m-rail::before { top: 12px; }
        .m-stop:last-child .m-rail::before { bottom: auto; height: 12px; }
        .m-rail::after { content: ''; position: absolute; left: 7px; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: var(--paper); border: 3px solid var(--ink); }
        .m-stop:last-child .m-rail::after { background: var(--brand); border-color: var(--brand); }
        .m-stop div:last-child { padding-bottom: 36px; }
        .m-stop h3 { font-size: 1.12rem; font-weight: 600; color: var(--text); }
        .m-stop p { margin-top: 4px; color: var(--muted); max-width: 60ch; }
        @media (max-width: 560px) {
            .m-stop { grid-template-columns: 64px 24px 1fr; column-gap: 8px; }
            .m-stop time { font-size: 1.1rem; }
            .m-rail::before { left: 11px; } .m-rail::after { left: 5px; }
        }

        /* Học phí */
        .m-fee { display: grid; gap: 48px; align-items: start; }
        @media (min-width: 960px) { .m-fee { grid-template-columns: 1fr 1fr; gap: 72px; } }
        .m-list { margin-top: 28px; display: grid; gap: 14px; }
        .m-list li { display: grid; grid-template-columns: 22px 1fr; gap: 12px; color: var(--text); }
        .m-list i { color: var(--ink); margin-top: 5px; }
        .m-switch { display: inline-grid; grid-template-columns: 1fr 1fr; background: #ece7f6; border-radius: 14px; padding: 4px; margin-bottom: 18px; }
        .m-switch button { padding: 9px 18px; border-radius: 11px; font: inherit; font-size: .92rem; font-weight: 500; color: var(--muted); background: transparent; border: 0; cursor: pointer; }
        .m-switch button[aria-pressed="true"] { background: #fff; color: var(--ink); box-shadow: 0 1px 3px rgba(42, 36, 56, .15); }
        .m-slip { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 24px; box-shadow: 0 24px 48px -32px rgba(75, 46, 131, .5); }
        .m-slip-head { display: flex; justify-content: space-between; gap: 12px; align-items: baseline; border-bottom: 1px dashed var(--grid); padding-bottom: 14px; }
        .m-slip-head b { font-weight: 600; color: var(--text); }
        .m-slip-head span { color: var(--muted); font-size: .88rem; }
        .m-slip-row { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; font-size: .97rem; }
        .m-slip-row span:first-child { color: var(--muted); }
        .m-slip-total { border-top: 1px solid var(--line); margin-top: 6px; padding-top: 14px; font-weight: 600; font-size: 1.12rem; color: var(--ink); }
        .m-slip-foot { margin-top: 18px; display: grid; grid-template-columns: 76px 1fr; gap: 16px; align-items: center; background: var(--wash); border-radius: 14px; padding: 12px; font-size: .86rem; color: var(--muted); }
        .m-qr { width: 76px; height: 76px; background: #fff; border-radius: 8px; padding: 6px; }
        [x-cloak] { display: none !important; }

        /* Ai thấy gì */
        .m-roles { margin-top: 48px; display: grid; gap: 36px; }
        @media (min-width: 860px) { .m-roles { grid-template-columns: repeat(3, 1fr); gap: 40px; } }
        .m-role { border-top: 2px solid var(--ink); padding-top: 20px; }
        .m-role h3 { font-size: 1.2rem; font-weight: 600; color: var(--ink); display: flex; align-items: center; gap: 10px; }
        .m-role p { margin-top: 8px; color: var(--muted); }

        /* Kết */
        .m-end { display: grid; gap: 32px; align-items: center; }
        @media (min-width: 900px) { .m-end { grid-template-columns: 1.2fr 1fr; } }
        .m-end .m-vo { padding-top: 31px; padding-bottom: 31px; }
        .m-end .m-hand { font-size: 1.3rem; }

        .m-foot { border-top: 1px solid var(--line); padding: 28px 0 36px; font-size: .9rem; color: var(--muted); }
        .m-foot .m-wrap { display: flex; flex-wrap: wrap; gap: 14px 28px; justify-content: space-between; align-items: center; }
        .m-foot nav { display: flex; flex-wrap: wrap; gap: 8px 22px; }
        .m-foot a { color: var(--muted); text-decoration: none; }
        .m-foot a:hover { color: var(--ink); }

        /* Chuyển động duy nhất: các dòng nhật ký "được viết" lần lượt khi tải trang. */
        @media (prefers-reduced-motion: no-preference) {
            .m-write { opacity: 0; animation: m-ink .5s ease-out forwards; animation-delay: calc(var(--i) * 140ms + 200ms); }
            @keyframes m-ink { from { opacity: 0; clip-path: inset(0 100% 0 0); } to { opacity: 1; clip-path: inset(0 0 0 0); } }
        }
    </style>
    <script type="application/ld+json">
    {!! json_encode(['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'Mầm', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web, Android, iOS', 'inLanguage' => 'vi', 'url' => $seoUrl, 'description' => $seoDescription, 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'VND']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="m-page antialiased">

<header class="m-wrap m-head">
    <a href="{{ route('home') }}" class="m-logo" aria-label="Mầm — trang chủ"><i class="fa-solid fa-seedling" aria-hidden="true"></i><b>Mầm</b></a>
    <nav aria-label="Điều hướng chính">
        <a href="#mot-ngay" class="m-nav-hide">Một ngày ở nhóm trẻ</a>
        <a href="#hoc-phi" class="m-nav-hide">Học phí</a>
        <a href="{{ route('login') }}">Đăng nhập</a>
        @if(\App\Models\PlatformSetting::registrationOpen())
            <a href="{{ route('register') }}" class="m-btn m-btn-primary m-btn-sm">Dùng miễn phí</a>
        @endif
    </nav>
</header>

<main>
    {{-- HERO — chính là sản phẩm Mầm tạo ra mỗi chiều: trang nhật ký của bé gửi phụ huynh. --}}
    <section class="m-wrap m-hero">
        <div>
            <h1>Cô ghi vài chạm, bố mẹ biết cả ngày của con.</h1>
            <p class="m-lede">Mầm là sổ điểm danh, sổ nhật ký bé và sổ học phí của nhóm trẻ gia đình — gói trong điện thoại, mở là dùng, không phải cài đặt gì.</p>
            <div class="m-cta">
                @if(\App\Models\PlatformSetting::registrationOpen())
                    <a href="{{ route('register') }}" class="m-btn m-btn-primary"><i class="fa-solid fa-seedling" aria-hidden="true"></i>Tạo nhóm trẻ miễn phí</a>
                    <a href="{{ route('login') }}" class="m-btn m-btn-ghost">Tôi đã có tài khoản</a>
                @else
                    <a href="{{ route('login') }}" class="m-btn m-btn-primary">Đăng nhập</a>
                @endif
            </div>
            <p class="m-fine">Dành cho nhóm trẻ gia đình, nhóm trẻ độc lập nhận bé 3–36 tháng, từ vài bé đến vài chục bé.</p>
        </div>

        <figure class="m-vo" aria-label="Ví dụ trang nhật ký một ngày của bé Na mà cô gửi cho phụ huynh">
            <div class="m-hand">
                <p class="m-vo-title m-write" style="--i:0">Nhật ký bé Na, thứ hai 12/10</p>
                @foreach([
                    ['7:20', 'Mẹ đưa đến lớp, bé vui vẻ'],
                    ['9:00', 'Uống sữa 180 ml'],
                    ['10:00', 'Tô màu, hát "Cả nhà thương nhau"'],
                    ['11:15', 'Cháo bí đỏ, ăn hết cả bát'],
                    ['12:00', 'Ngủ trưa 2 giờ 15 phút'],
                    ['14:30', 'Nhiệt độ 37,0°C, thay tã 2 lần'],
                    ['16:40', 'Bà ngoại đón về'],
                ] as $i => [$t, $note])
                    <p class="m-vo-row m-write" style="--i:{{ $i + 1 }}"><span>{{ $t }}</span><span>{{ $note }}</span></p>
                @endforeach
                <p class="m-write" style="--i:8">Cô dặn: mai mẹ gửi thêm bộ quần áo nhé!</p>
            </div>
            <figcaption class="m-vo-stamp"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Đã gửi cho mẹ bé Na lúc 17:05</span></figcaption>
        </figure>
    </section>

    {{-- MỘT NGÀY — trình tự thật theo giờ trong ngày, nên dùng dòng thời gian thay cho lưới thẻ tính năng. --}}
    <section id="mot-ngay" class="m-section m-wash">
        <div class="m-wrap m-day-layout">
            <div class="m-day-intro">
                <h2 class="m-h2">Một ngày ở nhóm trẻ, phần sổ sách để Mầm lo.</h2>
                <p class="m-sub">Từ lúc bé đến lớp tới lúc bà ngoại đón về, mỗi việc chỉ vài chạm — và tất cả tự gom lại thành báo cáo cuối ngày.</p>
            </div>

            <ol class="m-day">
                @foreach([
                    ['7:15', 'Điểm danh', 'Có mặt, nghỉ có phép, nghỉ không phép — giờ đến tự ghi. Bấm "Các bé còn lại đều có mặt" là xong cả lớp.'],
                    ['9:00', 'Sữa, thay tã', 'Ghi cho cả lớp một lần. Dùng sữa, tã trong kho thì tự trừ theo số lượng của từng bé, sắp hết là được nhắc.'],
                    ['11:00', 'Bữa trưa', 'Thực đơn tuần hiện sẵn lúc ghi, chọn bé ăn hết, gần hết hay ăn ít. Bé có dị ứng được nhắc ngay ở màn Hôm nay.'],
                    ['12:00', 'Ngủ trưa', 'Nhập giờ ngủ và giờ dậy, Mầm tự tính bé ngủ bao lâu.'],
                    ['14:00', 'Thuốc theo giờ, đo nhiệt độ', 'Việc định kỳ tự hiện đúng giờ. Bấm "Xong" là ghi vào sổ của các bé đang có mặt, bỏ qua bé vắng. Từ 37,5°C có cảnh báo sốt.'],
                    ['16:30', 'Đón về', 'Ghi giờ về và người đón, kèm lời nhắc ai được phép đón bé.'],
                    ['17:00', 'Báo cáo cho phụ huynh', 'Mỗi bé một đoạn tin nhắn: ăn, ngủ, vệ sinh, nhiệt độ, lời cô dặn. Bấm "Sao chép" rồi gửi qua tin nhắn, hoặc để bố mẹ tự xem qua đường link riêng của con.'],
                ] as [$t, $title, $desc])
                    <li class="m-stop">
                        <time>{{ $t }}</time>
                        <span class="m-rail" aria-hidden="true"></span>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $desc }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- HỌC PHÍ — có bản xem thử gửi tháng / gửi ngày để thấy ngay cách tính. --}}
    <section id="hoc-phi" class="m-section">
        <div class="m-wrap m-fee">
            <div>
                <h2 class="m-h2">Gửi tháng hay gửi ngày, học phí đều tự ra.</h2>
                <p class="m-sub">Mầm lấy số ngày bé có mặt từ điểm danh để tính tiền ăn — và cả học phí, nếu bé gửi theo ngày. Cả nhóm một mức chung, từng bé vẫn đặt được cách tính và mức riêng.</p>
                <ul class="m-list">
                    <li><i class="fa-solid fa-qrcode" aria-hidden="true"></i><span>Phiếu báo dạng tin nhắn kèm mã VietQR — phụ huynh quét là điền sẵn số tiền và nội dung chuyển khoản.</span></li>
                    <li><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i><span>Thu nhiều lần, phụ thu đồng phục, giảm trừ anh chị em. Phiếu đã sửa tay được giữ nguyên khi cập nhật hàng loạt.</span></li>
                    <li><i class="fa-solid fa-chart-column" aria-hidden="true"></i><span>Báo cáo thu chi theo tháng, công nợ các tháng trước, tải danh sách công nợ về mở bằng Excel.</span></li>
                </ul>
            </div>

            <div x-data="{ mode: 'daily' }">
                <div class="m-switch" role="group" aria-label="Xem phiếu học phí theo cách tính">
                    <button type="button" :aria-pressed="(mode === 'monthly').toString()" aria-pressed="false" @click="mode = 'monthly'">Gửi tháng</button>
                    <button type="button" :aria-pressed="(mode === 'daily').toString()" aria-pressed="true" @click="mode = 'daily'">Gửi ngày</button>
                </div>
                <div class="m-slip" aria-live="polite">
                    <div class="m-slip-head"><b>Phiếu báo học phí tháng 10</b><span>Bé Trần Gia Bảo</span></div>
                    <div x-show="mode === 'monthly'" x-cloak>
                        <div class="m-slip-row"><span>Học phí tháng</span><span>2.500.000đ</span></div>
                        <div class="m-slip-row"><span>Tiền ăn: 20 ngày × 35.000đ</span><span>700.000đ</span></div>
                        <div class="m-slip-row"><span>Giảm trừ anh chị em</span><span>−200.000đ</span></div>
                        <div class="m-slip-row m-slip-total"><span>Tổng cộng</span><span>3.000.000đ</span></div>
                    </div>
                    <div x-show="mode === 'daily'">
                        <div class="m-slip-row"><span>Học phí: 14 ngày × 150.000đ</span><span>2.100.000đ</span></div>
                        <div class="m-slip-row"><span>Tiền ăn: 14 ngày × 35.000đ</span><span>490.000đ</span></div>
                        <div class="m-slip-row"><span>Nghỉ có phép 3 ngày</span><span>không tính</span></div>
                        <div class="m-slip-row m-slip-total"><span>Tổng cộng</span><span>2.590.000đ</span></div>
                    </div>
                    <div class="m-slip-foot">
                        {{-- Hình minh hoạ mã QR (không phải mã thật). --}}
                        <svg class="m-qr" viewBox="0 0 21 21" aria-hidden="true" shape-rendering="crispEdges">
                            <path fill="#2a2438" d="M0 0h7v7H0zM14 0h7v7h-7zM0 14h7v7H0z"/>
                            <path fill="#fff" d="M1 1h5v5H1zM15 1h5v5h-5zM1 15h5v5H1z"/>
                            <path fill="#2a2438" d="M2 2h3v3H2zM16 2h3v3h-3zM2 16h3v3H2zM8 1h1v2H8zM10 0h2v1h-2zM9 4h2v2H9zM12 3h1v3h-1zM8 8h2v1H8zM11 8h3v1h-3zM15 8h1v2h-1zM17 9h3v1h-3zM0 9h2v1H0zM3 8h3v2H3zM8 10h1v3H8zM10 11h2v2h-2zM13 10h2v2h-2zM16 11h1v2h-1zM18 12h3v1h-3zM9 14h2v1H9zM12 14h1v3h-1zM14 13h2v2h-2zM17 14h1v1h-1zM19 15h2v2h-2zM8 16h3v1H8zM14 16h3v2h-3zM9 18h2v3H9zM12 19h3v1h-3zM16 19h1v2h-1zM18 18h1v1h-1zM20 19h1v2h-1z"/>
                        </svg>
                        <span>Phụ huynh quét mã bằng app ngân hàng, số tiền và nội dung "HP 2610 TRAN GIA BAO" đã điền sẵn.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- AI THẤY GÌ --}}
    <section class="m-section m-wash">
        <div class="m-wrap">
            <h2 class="m-h2">Mỗi người thấy đúng phần của mình.</h2>
            <div class="m-roles">
                <div class="m-role">
                    <h3><i class="fa-solid fa-house-chimney" aria-hidden="true"></i>Chủ nhóm</h3>
                    <p>Toàn quyền: hồ sơ bé, lớp theo độ tuổi, học phí, chi phí, kho sữa – tã, báo cáo thu chi. Có nhiều cơ sở vẫn quản lý trong một tài khoản.</p>
                </div>
                <div class="m-role">
                    <h3><i class="fa-solid fa-person-chalkboard" aria-hidden="true"></i>Cô giáo, bảo mẫu</h3>
                    <p>Mỗi cô một tài khoản để điểm danh, ghi nhật ký và làm việc trong ngày. Không xem được học phí hay chi phí.</p>
                </div>
                <div class="m-role">
                    <h3><i class="fa-solid fa-people-roof" aria-hidden="true"></i>Phụ huynh</h3>
                    <p>Không cần tài khoản hay cài ứng dụng. Mở đường link riêng của con để xem nhật ký 7 ngày, thực đơn tuần, cân đo và học phí cần đóng.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- KẾT --}}
    <section class="m-section">
        <div class="m-wrap m-end">
            <div>
                <h2 class="m-h2">Sáng mai điểm danh bằng Mầm.</h2>
                <p class="m-sub">Đăng ký mất một phút. Thêm hồ sơ các bé, đặt học phí, mời cô giáo — vậy là sẵn sàng cho ngày đầu tiên.</p>
                <div class="m-cta">
                    @if(\App\Models\PlatformSetting::registrationOpen())
                        <a href="{{ route('register') }}" class="m-btn m-btn-primary"><i class="fa-solid fa-seedling" aria-hidden="true"></i>Tạo nhóm trẻ miễn phí</a>
                        <a href="{{ route('login') }}" class="m-btn m-btn-ghost">Đăng nhập</a>
                    @else
                        <a href="{{ route('login') }}" class="m-btn m-btn-primary">Đăng nhập</a>
                    @endif
                </div>
            </div>
            <div class="m-vo" aria-hidden="true">
                <div class="m-hand">
                    <p>Việc cần làm tối nay:</p>
                    <p>☑ Tạo tài khoản Mầm</p>
                    <p>☐ Thêm hồ sơ các bé</p>
                    <p>☐ Gửi link cho phụ huynh</p>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="m-foot">
    <div class="m-wrap">
        <p>© {{ date('Y') }} Mầm — chăm bé mỗi ngày, bố mẹ yên tâm.</p>
        <nav aria-label="Liên kết cuối trang">
            <a href="{{ route('pages.about') }}">Giới thiệu</a>
            <a href="{{ route('pages.terms') }}">Điều khoản sử dụng</a>
            <a href="{{ route('pages.privacy') }}">Chính sách bảo mật</a>
            <a href="{{ route('login') }}">Đăng nhập</a>
        </nav>
    </div>
</footer>
</body>
</html>
