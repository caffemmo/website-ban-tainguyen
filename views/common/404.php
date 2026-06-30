<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>404 - <?= __('Không tìm thấy trang'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            overflow: hidden;
            background: #0a0e1a;
            color: #e2e8f0;
        }

        .bg-gradient {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: linear-gradient(135deg, #0a0e1a 0%, #0a1a2e 25%, #0d2847 50%, #0a1a2e 75%, #0a0e1a 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%
            }

            50% {
                background-position: 100% 50%
            }
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .12;
            z-index: 1;
            animation: floatOrb 20s ease-in-out infinite
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: #3b82f6;
            top: -10%;
            left: -10%
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: #6366f1;
            bottom: -10%;
            right: -10%;
            animation-delay: -7s
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: #06b6d4;
            top: 50%;
            left: 60%;
            animation-delay: -3s
        }

        @keyframes floatOrb {

            0%,
            100% {
                transform: translate(0, 0) scale(1)
            }

            33% {
                transform: translate(30px, -40px) scale(1.05)
            }

            66% {
                transform: translate(-20px, 30px) scale(.95)
            }
        }

        .grid-overlay {
            position: fixed;
            inset: 0;
            z-index: 2;
            background-image: linear-gradient(rgba(255, 255, 255, .02) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .02) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .particles {
            position: fixed;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            overflow: hidden
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(59, 130, 246, .6);
            border-radius: 50%;
            animation: particleFloat linear infinite
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0
            }

            10% {
                opacity: 1
            }

            90% {
                opacity: 1
            }

            100% {
                transform: translateY(-10vh) scale(1);
                opacity: 0
            }
        }

        .page-wrapper {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }

        .page-card {
            text-align: center;
            max-width: 580px;
            width: 100%;
            background: rgba(15, 20, 40, .55);
            backdrop-filter: blur(24px) saturate(1.4);
            -webkit-backdrop-filter: blur(24px) saturate(1.4);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 28px;
            padding: 56px 40px 48px;
            box-shadow: 0 0 0 1px rgba(255, 255, 255, .05), 0 25px 60px rgba(0, 0, 0, .4), inset 0 1px 0 rgba(255, 255, 255, .08);
            animation: cardAppear .8s cubic-bezier(.16, 1, .3, 1) both;
        }

        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(.96)
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1)
            }
        }

        /* ── Large 404 number ── */
        .error-code {
            font-size: clamp(100px, 20vw, 140px);
            font-weight: 900;
            line-height: 1;
            margin-bottom: 8px;
            letter-spacing: -6px;
            background: linear-gradient(135deg, rgba(59, 130, 246, .2) 0%, rgba(99, 102, 241, .15) 50%, rgba(6, 182, 212, .1) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            animation: glitchText 5s ease-in-out infinite;
        }

        .error-code::after {
            content: '404';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            background: linear-gradient(135deg, #60a5fa 0%, #818cf8 50%, #22d3ee 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: .6;
            animation: glitchOverlay 5s ease-in-out infinite;
        }

        @keyframes glitchText {

            0%,
            100% {
                transform: translate(0)
            }

            2% {
                transform: translate(-2px, 1px)
            }

            4% {
                transform: translate(2px, -1px)
            }

            6% {
                transform: translate(0)
            }
        }

        @keyframes glitchOverlay {

            0%,
            100% {
                clip-path: inset(0 0 100% 0)
            }

            2% {
                clip-path: inset(20% 0 60% 0)
            }

            4% {
                clip-path: inset(60% 0 10% 0)
            }

            6% {
                clip-path: inset(0 0 100% 0)
            }
        }

        /* ── Floating particles around 404 ── */
        .code-container {
            position: relative;
            display: inline-block
        }

        .float-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            animation: dotFloat 3s ease-in-out infinite;
        }

        .float-dot:nth-child(2) {
            background: #60a5fa;
            top: 20%;
            left: -10%;
            animation-delay: 0s
        }

        .float-dot:nth-child(3) {
            background: #818cf8;
            top: 60%;
            right: -8%;
            animation-delay: .5s
        }

        .float-dot:nth-child(4) {
            background: #22d3ee;
            bottom: 10%;
            left: 15%;
            animation-delay: 1s
        }

        .float-dot:nth-child(5) {
            background: #a78bfa;
            top: 10%;
            right: 20%;
            animation-delay: 1.5s
        }

        @keyframes dotFloat {

            0%,
            100% {
                transform: translateY(0) scale(1);
                opacity: .6
            }

            50% {
                transform: translateY(-10px) scale(1.3);
                opacity: 1
            }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 100px;
            background: rgba(59, 130, 246, .1);
            border: 1px solid rgba(59, 130, 246, .25);
            color: #60a5fa;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: clamp(22px, 4vw, 28px);
            font-weight: 700;
            letter-spacing: -.3px;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #e2e8f0 0%, #93c5fd 50%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-desc {
            font-size: 15px;
            line-height: 1.7;
            color: #94a3b8;
            font-weight: 400;
            max-width: 420px;
            margin: 0 auto 32px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 14px;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .3s cubic-bezier(.16, 1, .3, 1);
            box-shadow: 0 4px 20px rgba(59, 130, 246, .3);
        }

        .action-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 30px rgba(59, 130, 246, .45)
        }

        .action-btn:active {
            transform: translateY(0) scale(.98)
        }

        .action-btn svg {
            width: 16px;
            height: 16px
        }

        .secondary-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all .3s ease;
            margin-left: 12px;
        }

        .secondary-btn:hover {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(255, 255, 255, .15);
            color: #e2e8f0;
            transform: translateY(-1px)
        }

        .secondary-btn svg {
            width: 14px;
            height: 14px
        }

        .btn-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap
        }

        .page-footer {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, .06)
        }

        .footer-text {
            font-size: 12px;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px
        }

        .footer-text svg {
            width: 14px;
            height: 14px;
            opacity: .5
        }

        @media(max-width:640px) {
            .page-card {
                padding: 40px 24px 36px;
                border-radius: 22px
            }

            .error-code {
                letter-spacing: -4px
            }

            .btn-group {
                flex-direction: column
            }

            .secondary-btn {
                margin-left: 0
            }
        }
    </style>
</head>

<body>
    <div class="bg-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>
    <div class="particles" id="particles"></div>

    <div class="page-wrapper">
        <div class="page-card">
            <div class="code-container">
                <div class="error-code">404</div>
                <div class="float-dot"></div>
                <div class="float-dot"></div>
                <div class="float-dot"></div>
                <div class="float-dot"></div>
            </div>

            <div class="status-badge"><?= __('Không tìm thấy'); ?></div>

            <h1 class="page-title"><?= __('Trang bạn tìm kiếm không tồn tại'); ?></h1>
            <p class="page-desc"><?= __('Trang này có thể đã bị xóa, đổi tên hoặc tạm thời không khả dụng. Hãy kiểm tra lại đường dẫn hoặc quay về trang chủ.'); ?></p>

            <div class="btn-group">
                <a href="<?= base_url(''); ?>" class="action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1" />
                    </svg>
                    <?= __('Về trang chủ'); ?>
                </a>
                <a href="javascript:history.back();" class="secondary-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7" />
                    </svg>
                    <?= __('Quay lại'); ?>
                </a>
            </div>

            <div class="page-footer">
                <div class="footer-text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4M12 16h.01" />
                    </svg>
                    <?= __('Mã lỗi'); ?>: 404 Not Found
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var c = document.getElementById('particles');
            if (!c) return;
            for (var i = 0; i < 35; i++) {
                var p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.animationDuration = (8 + Math.random() * 12) + 's';
                p.style.animationDelay = Math.random() * 10 + 's';
                p.style.width = p.style.height = (1 + Math.random() * 3) + 'px';
                var colors = ['rgba(59,130,246,.5)', 'rgba(99,102,241,.4)', 'rgba(96,165,250,.3)', 'rgba(6,182,212,.3)'];
                p.style.background = colors[Math.floor(Math.random() * colors.length)];
                c.appendChild(p);
            }
        })();
    </script>
</body>

</html>