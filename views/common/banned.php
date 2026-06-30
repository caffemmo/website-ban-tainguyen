<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Tài khoản bị khóa'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background: linear-gradient(135deg, #0a0e1a 0%, #2d1a0a 25%, #1a1000 50%, #2d1a0a 75%, #0a0e1a 100%);
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
            background: #f59e0b;
            top: -10%;
            left: -10%
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: #d97706;
            bottom: -10%;
            right: -10%;
            animation-delay: -7s
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: #ef4444;
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
            background: rgba(245, 158, 11, .6);
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

        .icon-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 36px;
            position: relative;
            height: 100px;
        }

        .pulse-ring {
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 2px solid rgba(245, 158, 11, .25);
            animation: pulseRing 2.5s ease-out infinite;
        }

        .pulse-ring:nth-child(2) {
            animation-delay: .8s
        }

        @keyframes pulseRing {
            0% {
                transform: scale(.8);
                opacity: .6
            }

            100% {
                transform: scale(1.8);
                opacity: 0
            }
        }

        .icon-lock {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(245, 158, 11, .15), rgba(217, 119, 6, .1));
            border: 2px solid rgba(245, 158, 11, .3);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: iconShake 4s ease-in-out infinite;
        }

        @keyframes iconShake {

            0%,
            100% {
                transform: rotate(0)
            }

            5% {
                transform: rotate(-3deg)
            }

            10% {
                transform: rotate(3deg)
            }

            15% {
                transform: rotate(0)
            }
        }

        .icon-lock svg {
            width: 40px;
            height: 40px;
            stroke: #f59e0b;
            fill: none;
            stroke-width: 1.8
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 100px;
            background: rgba(245, 158, 11, .1);
            border: 1px solid rgba(245, 158, 11, .25);
            color: #fbbf24;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 8px rgba(245, 158, 11, .6);
            animation: dotBlink 1.4s ease-in-out infinite;
        }

        @keyframes dotBlink {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .3
            }
        }

        .page-title {
            font-size: clamp(24px, 5vw, 32px);
            font-weight: 800;
            letter-spacing: -.5px;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #e2e8f0 0%, #fbbf24 50%, #f59e0b 100%);
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
            margin: 0 auto 28px;
        }

        .custom-content {
            font-size: 14px;
            line-height: 1.7;
            color: #94a3b8;
            margin-bottom: 28px;
            padding: 16px 20px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .06);
            text-align: left;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all .3s cubic-bezier(.16, 1, .3, 1);
            box-shadow: 0 4px 20px rgba(245, 158, 11, .3);
        }

        .action-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 30px rgba(245, 158, 11, .45)
        }

        .action-btn:active {
            transform: translateY(0) scale(.98)
        }

        .action-btn svg {
            width: 16px;
            height: 16px
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
            <div class="icon-container">
                <div class="pulse-ring"></div>
                <div class="pulse-ring"></div>
                <div class="icon-lock">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        <circle cx="12" cy="16" r="1" />
                    </svg>
                </div>
            </div>

            <div class="status-badge">
                <span class="status-dot"></span>
                <?= __('Tài khoản bị khóa'); ?>
            </div>

            <h1 class="page-title"><?= __('Tài khoản đã bị tạm khóa'); ?></h1>
            <p class="page-desc"><?= __('Tài khoản của bạn đã bị tạm khóa bởi quản trị viên. Vui lòng liên hệ bộ phận hỗ trợ để được giải quyết.'); ?></p>

            <div class="custom-content"><?= $CMSNT->site('html_banned'); ?></div>

            <a href="<?= base_url(''); ?>" class="action-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1" />
                </svg>
                <?= __('Về trang chủ'); ?>
            </a>

            <div class="page-footer">
                <div class="footer-text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4M12 16h.01" />
                    </svg>
                    <?= __('Liên hệ quản trị viên để được hỗ trợ'); ?>
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
                var colors = ['rgba(245,158,11,.5)', 'rgba(217,119,6,.4)', 'rgba(251,191,36,.3)', 'rgba(239,68,68,.3)'];
                p.style.background = colors[Math.floor(Math.random() * colors.length)];
                c.appendChild(p);
            }
        })();
    </script>
</body>

</html>