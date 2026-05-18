/* CODEGA Finans - app.js */

(function () {
    'use strict';

    // Sidebar toggle (mobil)
    const burger = document.querySelector('.cf-burger');
    const sidebar = document.querySelector('.cf-sidebar');
    const backdrop = document.querySelector('.cf-backdrop');

    function toggleNav(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        if (backdrop) backdrop.classList.toggle('open', open);
    }

    if (burger) {
        burger.addEventListener('click', function () {
            const open = !sidebar.classList.contains('open');
            toggleNav(open);
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () { toggleNav(false); });
    }

    // Para input girişlerini düzenle (TR locale: 1.234,56)
    document.querySelectorAll('input[data-money]').forEach(function (el) {
        el.addEventListener('blur', function () {
            const raw = (el.value || '').replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
            const num = parseFloat(raw);
            if (!isNaN(num)) {
                el.value = num.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        });
    });

    // Form submit'inde tekrar tıklamayı engelle
    document.querySelectorAll('form[data-once]').forEach(function (f) {
        f.addEventListener('submit', function () {
            const b = f.querySelector('button[type=submit]');
            if (b) { b.disabled = true; b.style.opacity = .7; b.textContent = 'Gönderiliyor…'; }
        });
    });

    // Otomatik kapanan flash
    document.querySelectorAll('.cf-flash[data-auto]').forEach(function (el) {
        setTimeout(function () { el.style.transition = 'opacity .4s'; el.style.opacity = 0; }, 5000);
        setTimeout(function () { el.remove(); }, 5500);
    });

    // Donut çizici: data-donut="{labels:[],values:[],colors:[]}"
    document.querySelectorAll('[data-donut]').forEach(function (el) {
        try {
            const cfg = JSON.parse(el.getAttribute('data-donut'));
            renderDonut(el, cfg);
        } catch (e) { console.warn('donut parse', e); }
    });

    function renderDonut(container, cfg) {
        const labels = cfg.labels || [];
        const values = (cfg.values || []).map(Number);
        const colors = cfg.colors || ['#10b981', '#ef4444', '#f59e0b', '#3b82f6', '#a855f7', '#06b6d4'];
        const total = values.reduce((a,b)=>a+b,0);
        if (total <= 0) {
            container.innerHTML = '<div class="cf-empty"><div class="icon">○</div>Bu ay için veri yok</div>';
            return;
        }

        const size = 260;
        const r = size/2 - 18;
        const cx = size/2, cy = size/2;
        let start = -Math.PI/2;

        const svgNS = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', '100%');

        values.forEach(function (v, i) {
            const angle = (v/total) * Math.PI * 2;
            const end = start + angle;
            const x1 = cx + r*Math.cos(start), y1 = cy + r*Math.sin(start);
            const x2 = cx + r*Math.cos(end),   y2 = cy + r*Math.sin(end);
            const large = angle > Math.PI ? 1 : 0;
            const d = [
                `M ${cx} ${cy}`,
                `L ${x1} ${y1}`,
                `A ${r} ${r} 0 ${large} 1 ${x2} ${y2}`,
                'Z'
            ].join(' ');
            const path = document.createElementNS(svgNS, 'path');
            path.setAttribute('d', d);
            path.setAttribute('fill', colors[i % colors.length]);
            path.setAttribute('stroke', '#fff');
            path.setAttribute('stroke-width', '2');
            svg.appendChild(path);
            start = end;
        });
        // İç beyaz daire
        const inner = document.createElementNS(svgNS, 'circle');
        inner.setAttribute('cx', cx); inner.setAttribute('cy', cy);
        inner.setAttribute('r', r * 0.58);
        inner.setAttribute('fill', '#fff');
        svg.appendChild(inner);

        // Merkez yazısı
        const t1 = document.createElementNS(svgNS, 'text');
        t1.setAttribute('x', cx); t1.setAttribute('y', cy - 4);
        t1.setAttribute('text-anchor', 'middle');
        t1.setAttribute('font-size', '13');
        t1.setAttribute('fill', '#64748b');
        t1.textContent = 'Toplam Gider';
        svg.appendChild(t1);

        const t2 = document.createElementNS(svgNS, 'text');
        t2.setAttribute('x', cx); t2.setAttribute('y', cy + 18);
        t2.setAttribute('text-anchor', 'middle');
        t2.setAttribute('font-size', '20');
        t2.setAttribute('font-weight', '700');
        t2.setAttribute('fill', '#0f172a');
        t2.textContent = total.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
        svg.appendChild(t2);

        container.innerHTML = '';
        container.appendChild(svg);

        // Legend
        if (cfg.legend !== false) {
            const lg = document.createElement('div');
            lg.className = 'cf-donut-legend';
            lg.style.cssText = 'display:flex;flex-wrap:wrap;gap:10px 18px;justify-content:center;margin-top:14px;font-size:13px;';
            labels.forEach(function (lbl, i) {
                const pct = total > 0 ? Math.round((values[i] / total) * 100) : 0;
                const item = document.createElement('div');
                item.style.cssText = 'display:flex;align-items:center;gap:8px;';
                item.innerHTML = `<span style="width:10px;height:10px;border-radius:3px;background:${colors[i % colors.length]}"></span><span><b>${lbl}</b> · %${pct}</span>`;
                lg.appendChild(item);
            });
            container.appendChild(lg);
        }
    }

    // Mini sparkline (gelir/gider trend)
    document.querySelectorAll('[data-spark]').forEach(function (el) {
        try {
            const data = JSON.parse(el.getAttribute('data-spark'));
            renderSpark(el, data);
        } catch (e) {}
    });
    function renderSpark(el, data) {
        const series = data.series || [];
        const color = data.color || '#2563eb';
        if (series.length < 2) { return; }
        const w = el.clientWidth || 200, h = el.clientHeight || 60;
        const max = Math.max.apply(null, series);
        const min = Math.min.apply(null, series);
        const range = (max - min) || 1;
        const step = w / (series.length - 1);
        let d = '';
        series.forEach(function (v, i) {
            const x = i * step;
            const y = h - ((v - min) / range) * (h - 6) - 3;
            d += (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1) + ' ';
        });
        el.innerHTML = `<svg viewBox="0 0 ${w} ${h}" width="100%" height="100%">
            <path d="${d}" fill="none" stroke="${color}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`;
    }
})();
