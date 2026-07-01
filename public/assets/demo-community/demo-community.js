(function () {
    const canvas = document.getElementById("techCanvas");
    const ctx = canvas && canvas.getContext("2d");
    let width = 0;
    let height = 0;
    let dots = [];

    function resize() {
        if (!ctx) return;
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        width = canvas.clientWidth;
        height = canvas.clientHeight;
        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        const count = Math.max(55, Math.min(130, Math.floor((width * height) / 13000)));
        dots = Array.from({ length: count }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * 0.28,
            vy: (Math.random() - 0.5) * 0.28,
            r: Math.random() * 1.7 + 0.6
        }));
    }

    function draw() {
        if (!ctx) return;
        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = "rgba(94, 234, 212, 0.72)";
        ctx.strokeStyle = "rgba(125, 211, 252, 0.18)";

        for (let i = 0; i < dots.length; i += 1) {
            const p = dots[i];
            p.x += p.vx;
            p.y += p.vy;
            if (p.x < 0 || p.x > width) p.vx *= -1;
            if (p.y < 0 || p.y > height) p.vy *= -1;

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fill();

            for (let j = i + 1; j < dots.length; j += 1) {
                const q = dots[j];
                const dx = p.x - q.x;
                const dy = p.y - q.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < 120) {
                    ctx.globalAlpha = 1 - distance / 120;
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(q.x, q.y);
                    ctx.stroke();
                    ctx.globalAlpha = 1;
                }
            }
        }

        window.requestAnimationFrame(draw);
    }

    resize();
    draw();
    window.addEventListener("resize", resize);

    const input = document.getElementById("chatInput");
    const send = document.getElementById("sendChat");
    const stream = document.getElementById("chatStream");

    function sendMessage() {
        const text = input.value.trim();
        if (!text) return;
        const line = document.createElement("div");
        line.className = "chat-line";
        line.innerHTML = "<b>Bạn</b><span></span>";
        line.querySelector("span").textContent = text;
        stream.appendChild(line);
        input.value = "";
        stream.scrollTop = stream.scrollHeight;
    }

    if (send && input && stream) {
        send.addEventListener("click", sendMessage);
        input.addEventListener("keydown", (event) => {
            if (event.key === "Enter") sendMessage();
        });
    }
})();
