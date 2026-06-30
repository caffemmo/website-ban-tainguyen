<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Bảo trì hệ thống'); ?></title>
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

        /* ── Animated gradient background ── */
        .bg-gradient {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: linear-gradient(135deg, #0a0e1a 0%, #1a1040 25%, #0d1f3c 50%, #0a2540 75%, #0a0e1a 100%);
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

        /* ── Floating orbs ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .15;
            z-index: 1;
            animation: floatOrb 20s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: #6366f1;
            top: -10%;
            left: -10%;
            animation-delay: 0s
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: #8b5cf6;
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

        /* ── Grid pattern overlay ── */
        .grid-overlay {
            position: fixed;
            inset: 0;
            z-index: 2;
            background-image:
                linear-gradient(rgba(255, 255, 255, .02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .02) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* ── Particles ── */
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
            background: rgba(139, 92, 246, .6);
            border-radius: 50%;
            animation: particleFloat linear infinite;
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

        /* ── Main container ── */
        .maintenance-wrapper {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }

        .maintenance-card {
            text-align: center;
            max-width: 580px;
            width: 100%;
            background: rgba(15, 20, 40, .55);
            backdrop-filter: blur(24px) saturate(1.4);
            -webkit-backdrop-filter: blur(24px) saturate(1.4);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 28px;
            padding: 56px 40px 48px;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, .05),
                0 25px 60px rgba(0, 0, 0, .4),
                inset 0 1px 0 rgba(255, 255, 255, .08);
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

        /* ── Gear animation ── */
        .gear-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 36px;
            position: relative;
            height: 100px;
        }

        .gear {
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round
        }

        .gear-lg {
            width: 90px;
            height: 90px;
            animation: spinCW 6s linear infinite;
        }

        .gear-sm {
            width: 56px;
            height: 56px;
            margin-left: -14px;
            margin-top: 28px;
            animation: spinCCW 4s linear infinite;
        }

        .gear-lg .gear {
            stroke: url(#gearGrad1);
            stroke-width: 2.5
        }

        .gear-sm .gear {
            stroke: url(#gearGrad2);
            stroke-width: 2.5
        }

        @keyframes spinCW {
            to {
                transform: rotate(360deg)
            }
        }

        @keyframes spinCCW {
            to {
                transform: rotate(-360deg)
            }
        }

        /* ── Glowing pulse ring behind icon ── */
        .pulse-ring {
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 2px solid rgba(139, 92, 246, .25);
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

        /* ── Badge ── */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 100px;
            background: rgba(251, 146, 60, .1);
            border: 1px solid rgba(251, 146, 60, .25);
            color: #fb923c;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
            animation: badgePulse 2s ease-in-out infinite;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #fb923c;
            box-shadow: 0 0 8px rgba(251, 146, 60, .6);
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

        @keyframes badgePulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(251, 146, 60, .15)
            }

            50% {
                box-shadow: 0 0 0 8px rgba(251, 146, 60, 0)
            }
        }

        /* ── Typography ── */
        .maintenance-title {
            font-size: clamp(26px, 5vw, 34px);
            font-weight: 800;
            letter-spacing: -.5px;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #e2e8f0 0%, #a78bfa 50%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .maintenance-desc {
            font-size: 15px;
            line-height: 1.7;
            color: #94a3b8;
            font-weight: 400;
            max-width: 420px;
            margin: 0 auto 32px;
        }

        /* ── Progress bar ── */
        .progress-section {
            margin-bottom: 32px
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .progress-track {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, .06);
            border-radius: 100px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            border-radius: 100px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #a78bfa);
            background-size: 200% 100%;
            animation: progressMove 2s ease-in-out infinite, progressShimmer 1.5s ease infinite;
            width: 65%;
        }

        @keyframes progressMove {
            0% {
                width: 30%
            }

            50% {
                width: 75%
            }

            100% {
                width: 30%
            }
        }

        @keyframes progressShimmer {
            0% {
                background-position: 200% 0
            }

            100% {
                background-position: -200% 0
            }
        }

        /* ── Info cards row ── */
        .info-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .info-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .06);
            font-size: 13px;
            color: #94a3b8;
            transition: all .3s ease;
        }

        .info-chip:hover {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(255, 255, 255, .12);
            transform: translateY(-2px);
        }

        .info-chip svg {
            width: 16px;
            height: 16px;
            opacity: .6;
            flex-shrink: 0
        }

        /* ── Refresh button ── */
        .refresh-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            letter-spacing: .3px;
            transition: all .3s cubic-bezier(.16, 1, .3, 1);
            box-shadow: 0 4px 20px rgba(99, 102, 241, .3);
            text-decoration: none;
        }

        .refresh-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 30px rgba(99, 102, 241, .45);
        }

        .refresh-btn:active {
            transform: translateY(0) scale(.98)
        }

        .refresh-btn svg {
            width: 16px;
            height: 16px;
            animation: spinCW 2s linear infinite;
        }

        /* ── Footer ── */
        .maintenance-footer {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, .06);
        }

        .footer-text {
            font-size: 12px;
            color: #475569;
            font-weight: 400;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .footer-text svg {
            width: 14px;
            height: 14px;
            opacity: .5
        }

        /* ── Responsive ── */
        @media(max-width:640px) {
            .maintenance-card {
                padding: 40px 24px 36px;
                border-radius: 22px
            }

            .gear-lg {
                width: 70px;
                height: 70px
            }

            .gear-sm {
                width: 44px;
                height: 44px;
                margin-left: -10px;
                margin-top: 22px
            }

            .info-row {
                flex-direction: column;
                align-items: center
            }
        }
    </style>
</head>

<body>

    <!-- Background layers -->
    <div class="bg-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>

    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <!-- Main content -->
    <div class="maintenance-wrapper">
        <div class="maintenance-card">

            <!-- Animated gears -->
            <div class="gear-container">
                <div class="pulse-ring"></div>
                <div class="pulse-ring"></div>
                <svg class="gear-lg" viewBox="0 0 100 100">
                    <defs>
                        <linearGradient id="gearGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#818cf8" />
                            <stop offset="100%" stop-color="#6366f1" />
                        </linearGradient>
                    </defs>
                    <path class="gear" d="M50 15 L53 5 Q50 3 47 5 L50 15 M50 85 L53 95 Q50 97 47 95 L50 85 M15 50 L5 47 Q3 50 5 53 L15 50 M85 50 L95 53 Q97 50 95 47 L85 50 M25.3 25.3 L18 16 Q15.5 17.5 16 18 L25.3 25.3 M74.7 74.7 L82 84 Q84.5 82.5 84 82 L74.7 74.7 M25.3 74.7 L16 82 Q17.5 84.5 18 84 L25.3 74.7 M74.7 25.3 L84 18 Q82.5 15.5 82 16 L74.7 25.3" />
                    <circle class="gear" cx="50" cy="50" r="32" />
                    <circle class="gear" cx="50" cy="50" r="18" opacity=".5" />
                </svg>
                <svg class="gear-sm" viewBox="0 0 100 100">
                    <defs>
                        <linearGradient id="gearGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#a78bfa" />
                            <stop offset="100%" stop-color="#8b5cf6" />
                        </linearGradient>
                    </defs>
                    <path class="gear" d="M50 15 L53 5 Q50 3 47 5 L50 15 M50 85 L53 95 Q50 97 47 95 L50 85 M15 50 L5 47 Q3 50 5 53 L15 50 M85 50 L95 53 Q97 50 95 47 L85 50 M25.3 25.3 L18 16 Q15.5 17.5 16 18 L25.3 25.3 M74.7 74.7 L82 84 Q84.5 82.5 84 82 L74.7 74.7 M25.3 74.7 L16 82 Q17.5 84.5 18 84 L25.3 74.7 M74.7 25.3 L84 18 Q82.5 15.5 82 16 L74.7 25.3" />
                    <circle class="gear" cx="50" cy="50" r="32" />
                    <circle class="gear" cx="50" cy="50" r="18" opacity=".5" />
                </svg>
            </div>

            <!-- Status badge -->
            <div class="status-badge">
                <span class="status-dot"></span>
                <?= __('Đang bảo trì'); ?>
            </div>

            <!-- Title & description -->
            <h1 class="maintenance-title"><?= __('Hệ thống đang nâng cấp'); ?></h1>
            <p class="maintenance-desc">
                <?= __('Chúng tôi đang thực hiện bảo trì và nâng cấp hệ thống để mang đến trải nghiệm tốt hơn. Vui lòng quay lại sau ít phút.'); ?>
            </p>

            <!-- Progress bar -->
            <div class="progress-section">
                <div class="progress-label">
                    <span><?= __('Tiến trình bảo trì'); ?></span>
                    <span><?= __('Đang xử lý...'); ?></span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <!-- Refresh button -->
            <a href="javascript:location.reload();" class="refresh-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M1 4v6h6" />
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
                </svg>
                <?= __('Tải lại trang'); ?>
            </a>

            <!-- Footer -->
            <div class="maintenance-footer">
                <div class="footer-text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <?= __('Cảm ơn bạn đã kiên nhẫn chờ đợi'); ?>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Generate floating particles
        (function() {
            var c = document.getElementById('particles');
            if (!c) return;
            for (var i = 0; i < 40; i++) {
                var p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.animationDuration = (8 + Math.random() * 12) + 's';
                p.style.animationDelay = Math.random() * 10 + 's';
                p.style.width = p.style.height = (1 + Math.random() * 3) + 'px';
                var colors = ['rgba(139,92,246,.5)', 'rgba(99,102,241,.4)', 'rgba(129,140,248,.3)', 'rgba(6,182,212,.3)'];
                p.style.background = colors[Math.floor(Math.random() * colors.length)];
                c.appendChild(p);
            }
        })();
    </script>
</body>

</html>