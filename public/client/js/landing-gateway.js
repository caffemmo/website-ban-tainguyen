(function () {
    const canvas = document.getElementById("techCanvas");
    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext("2d");
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    let width = 0;
    let height = 0;
    let particles = [];
    let animationFrame = null;

    function resize() {
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        width = canvas.clientWidth;
        height = canvas.clientHeight;
        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

        const count = Math.max(48, Math.min(110, Math.floor((width * height) / 14000)));
        particles = Array.from({ length: count }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * 0.35,
            vy: (Math.random() - 0.5) * 0.35,
            r: Math.random() * 1.8 + 0.7
        }));
    }

    function draw() {
        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = "rgba(94, 234, 212, 0.72)";
        ctx.strokeStyle = "rgba(125, 211, 252, 0.16)";

        for (let i = 0; i < particles.length; i += 1) {
            const p = particles[i];
            if (!prefersReducedMotion) {
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0 || p.x > width) p.vx *= -1;
                if (p.y < 0 || p.y > height) p.vy *= -1;
            }

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fill();

            for (let j = i + 1; j < particles.length; j += 1) {
                const q = particles[j];
                const dx = p.x - q.x;
                const dy = p.y - q.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < 128) {
                    ctx.globalAlpha = 1 - distance / 128;
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(q.x, q.y);
                    ctx.stroke();
                    ctx.globalAlpha = 1;
                }
            }
        }

        animationFrame = window.requestAnimationFrame(draw);
    }

    resize();
    draw();

    window.addEventListener("resize", () => {
        window.cancelAnimationFrame(animationFrame);
        resize();
        draw();
    });
})();
