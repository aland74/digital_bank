import './bootstrap';

// ══════════════════════════════════════════════════════════════
// Distributed Bank — Premium Interactive Experience v2.0
// ══════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar Toggle ──────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const menuBtn = document.getElementById('mobile-menu-btn');

    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            sidebar?.classList.toggle('open');
            overlay?.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar?.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // ── Auto-dismiss alerts ─────────────────────────────────
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // ── Toggle switches ─────────────────────────────────────
    document.querySelectorAll('[data-toggle-form]').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const form = document.getElementById(this.dataset.toggleForm);
            if (form) {
                this.classList.toggle('active');
                form.submit();
            }
        });
    });

    // ── Animated counters ───────────────────────────────────
    document.querySelectorAll('[data-count-to]').forEach(el => {
        const target = parseFloat(el.dataset.countTo);
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        const decimals = el.dataset.decimals ? parseInt(el.dataset.decimals) : 0;
        const duration = 1500;
        const start = 0;
        const startTime = performance.now();

        function animate(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            const current = start + (target - start) * eased;

            el.textContent = prefix + current.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }) + suffix;

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }

        requestAnimationFrame(animate);
    });

    // ── Chart rendering (canvas-based) ──────────────────────
    document.querySelectorAll('[data-chart]').forEach(canvas => {
        const data = JSON.parse(canvas.dataset.chart);
        renderChart(canvas, data);
    });

    // ── Donut chart rendering ───────────────────────────────
    document.querySelectorAll('[data-donut]').forEach(canvas => {
        const data = JSON.parse(canvas.dataset.donut);
        renderDonutChart(canvas, data);
    });

    // ── Notification Dropdown ───────────────────────────────
    initNotificationDropdown();

    // ── Password Toggle ─────────────────────────────────────
    initPasswordToggles();

    // ── Loading Buttons ─────────────────────────────────────
    initLoadingButtons();

    // ── 3D Card Tilt ────────────────────────────────────────
    initCardTilt();

    // ── Confetti (on success pages) ─────────────────────────
    if (document.querySelector('.success-animation')) {
        setTimeout(launchConfetti, 500);
    }

    // ── Inline Form Validation ──────────────────────────────
    initInlineValidation();

    // ── Notification Polling ────────────────────────────────
    startNotificationPolling();
    // ── Theme Toggler ───────────────────────────────────────
    initThemeToggle();
});

// ══════════════════════════════════════════════════════════════
// Theme Toggle
// ══════════════════════════════════════════════════════════════
function initThemeToggle() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIconDark = document.querySelector('.theme-icon-dark');
    const themeIconLight = document.querySelector('.theme-icon-light');

    function isDarkMode() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function updateThemeIcons() {
        if (!themeIconDark || !themeIconLight) return;
        if (isDarkMode()) {
            // Currently dark, show sun icon (to switch to light)
            themeIconDark.style.display = 'inline';
            themeIconLight.style.display = 'none';
        } else {
            // Currently light, show moon icon (to switch to dark)
            themeIconDark.style.display = 'none';
            themeIconLight.style.display = 'inline';
        }
    }

    if (themeToggle) {
        updateThemeIcons();
        themeToggle.addEventListener('click', () => {
            if (isDarkMode()) {
                // Switch to light
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                // Switch to dark
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcons();

            // Re-render charts for new theme
            document.querySelectorAll('canvas').forEach(canvas => {
                if (canvas.dataset.chart) {
                    renderChart(canvas, JSON.parse(canvas.dataset.chart));
                } else if (canvas.dataset.donut) {
                    renderDonutChart(canvas, JSON.parse(canvas.dataset.donut));
                }
            });
        });
    }
}

// ══════════════════════════════════════════════════════════════
// Notification Dropdown
// ══════════════════════════════════════════════════════════════
function initNotificationDropdown() {
    const bellBtn = document.getElementById('notification-bell-btn');
    const dropdown = document.getElementById('notification-dropdown');

    if (!bellBtn || !dropdown) return;

    bellBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            fetchLatestNotifications();
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdown.classList.remove('show');
        }
    });
}

function startNotificationPolling() {
    // Only poll if user is logged in (we assume they are if the bell button exists)
    if (!document.getElementById('notification-bell-btn')) return;

    // Poll every 30 seconds
    setInterval(pollUnreadCount, 30000);
}

async function pollUnreadCount() {
    try {
        const response = await fetch('/notifications/unread-count');
        const data = await response.json();
        
        updateNotificationBadges(data.count, data.actionable);
    } catch (e) {
        console.error('Failed to poll notifications');
    }
}

async function fetchLatestNotifications() {
    try {
        const response = await fetch('/notifications/latest');
        const data = await response.json();
        
        updateNotificationBadges(data.unread_count, 0);
        renderNotificationDropdown(data.notifications);
    } catch (e) {
        console.error('Failed to fetch latest notifications');
    }
}

function updateNotificationBadges(count, actionableCount) {
    const bellDot = document.querySelector('.badge-dot');
    const sidebarBadge = document.querySelector('.badge-count');

    // Bell dot
    if (count > 0) {
        if (!bellDot) {
            const btn = document.getElementById('notification-bell-btn');
            if (btn) btn.innerHTML += '<span class="badge-dot"></span>';
        }
    } else {
        if (bellDot) bellDot.remove();
    }

    // Sidebar badge
    if (count > 0) {
        if (sidebarBadge) {
            sidebarBadge.textContent = count;
        } else {
            const notifLink = document.querySelector('.sidebar-link[href$="/notifications"]');
            if (notifLink) {
                notifLink.innerHTML += `<span class="badge-count">${count}</span>`;
            }
        }
    } else {
        if (sidebarBadge) sidebarBadge.remove();
    }
}

function renderNotificationDropdown(notifications) {
    const body = document.querySelector('.notification-list');
    if (!body) return;

    if (notifications.length === 0) {
        body.innerHTML = `
            <div style="padding:40px 20px;text-align:center;">
                <div style="font-size:28px;margin-bottom:8px;opacity:0.5;">🔔</div>
                <div class="text-muted text-sm">No notifications</div>
            </div>`;
        return;
    }

    body.innerHTML = notifications.map(notif => `
        <a href="${notif.read_url}" class="notification-item ${!notif.is_read ? 'unread' : ''}" onclick="event.preventDefault(); fetch('${notif.read_url}', {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}}).then(() => window.location='${notif.action_url || '/notifications'}');">
            <div class="notif-icon">
                ${notif.type_icon}
            </div>
            <div class="notif-content">
                <div class="notif-title">${notif.title}</div>
                <div class="notif-message">${notif.message}</div>
                <div class="notif-time">${notif.time}</div>
            </div>
        </a>
    `).join('');
}

// ══════════════════════════════════════════════════════════════
// Password Toggle (Show/Hide)
// ══════════════════════════════════════════════════════════════
function initPasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const wrapper = this.closest('.password-wrapper');
            const input = wrapper.querySelector('input');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
            } else {
                input.type = 'password';
                this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            }
        });
    });
}

// ══════════════════════════════════════════════════════════════
// Loading Button State
// ══════════════════════════════════════════════════════════════
function initLoadingButtons() {
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (this.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }
            this.dataset.submitting = 'true';
            
            const btn = this.querySelector('button[type="submit"]');
            if (btn && !btn.classList.contains('is-loading')) {
                btn.classList.add('is-loading');
                const textSpan = btn.querySelector('.btn-text');
                if (!textSpan) {
                    // Wrap existing text
                    btn.innerHTML = '<span class="btn-text">' + btn.innerHTML + '</span>';
                }
                // Disable button after a brief timeout to let standard browser submit initiate
                setTimeout(() => {
                    btn.disabled = true;
                }, 50);
            }
        });
    });
}

// ══════════════════════════════════════════════════════════════
// 3D Card Tilt Effect
// ══════════════════════════════════════════════════════════════
function initCardTilt() {
    document.querySelectorAll('.bank-card[data-tilt]').forEach(card => {
        const glow = card.querySelector('.card-glow');

        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -8;
            const rotateY = ((x - centerX) / centerX) * 8;

            card.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;

            if (glow) {
                glow.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(0,212,255,0.15), transparent 60%)`;
            }
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(800px) rotateX(0) rotateY(0) scale(1)';
            if (glow) {
                glow.style.background = 'transparent';
            }
        });
    });
}

// ══════════════════════════════════════════════════════════════
// Confetti Animation
// ══════════════════════════════════════════════════════════════
function launchConfetti() {
    const container = document.createElement('div');
    container.className = 'confetti-container';
    document.body.appendChild(container);

    const colors = ['#00d4ff', '#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ec4899', '#ef4444'];

    for (let i = 0; i < 60; i++) {
        const piece = document.createElement('div');
        piece.className = 'confetti-piece';
        piece.style.left = Math.random() * 100 + '%';
        piece.style.animationDelay = Math.random() * 1.5 + 's';
        piece.style.animationDuration = (Math.random() * 2 + 2) + 's';
        piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        piece.style.width = (Math.random() * 8 + 6) + 'px';
        piece.style.height = (Math.random() * 8 + 6) + 'px';
        piece.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        container.appendChild(piece);
    }

    setTimeout(() => container.remove(), 5000);
}

// ══════════════════════════════════════════════════════════════
// Inline Form Validation
// ══════════════════════════════════════════════════════════════
function initInlineValidation() {
    // Email fields
    document.querySelectorAll('input[type="email"][data-validate]').forEach(input => {
        input.addEventListener('blur', function () {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
            this.classList.toggle('is-valid', valid && this.value.length > 0);
            this.classList.toggle('is-invalid', !valid && this.value.length > 0);
        });
        input.addEventListener('input', function () {
            this.classList.remove('is-valid', 'is-invalid');
        });
    });

    // Required fields
    document.querySelectorAll('input[data-validate][required]').forEach(input => {
        if (input.type === 'email') return;
        input.addEventListener('blur', function () {
            this.classList.toggle('is-valid', this.value.trim().length > 0);
            this.classList.toggle('is-invalid', this.value.trim().length === 0);
        });
        input.addEventListener('input', function () {
            this.classList.remove('is-valid', 'is-invalid');
        });
    });

    // Password strength
    document.querySelectorAll('input[type="password"][data-strength]').forEach(input => {
        const indicator = document.getElementById(input.dataset.strength);
        if (!indicator) return;

        input.addEventListener('input', function () {
            const val = this.value;
            let strength = 0;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', 'var(--accent-red)', 'var(--accent-orange)', 'var(--accent-yellow)', 'var(--accent-green)'];

            indicator.innerHTML = val.length > 0
                ? `<div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                     <div style="flex:1;height:3px;background:var(--bg-tertiary);border-radius:4px;overflow:hidden;">
                       <div style="width:${strength * 25}%;height:100%;background:${colors[strength]};transition:all 0.3s;border-radius:4px;"></div>
                     </div>
                     <span style="font-size:11px;color:${colors[strength]};font-weight:600;">${labels[strength]}</span>
                   </div>`
                : '';
        });
    });
}

// ══════════════════════════════════════════════════════════════
// Mini Chart Renderer (Line Chart)
// ══════════════════════════════════════════════════════════════
function renderChart(canvas, data) {
    const ctx = canvas.getContext('2d');
    const width = canvas.width = canvas.offsetWidth * 2;
    const height = canvas.height = canvas.offsetHeight * 2;
    ctx.scale(2, 2);
    const w = canvas.offsetWidth;
    const h = canvas.offsetHeight;

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
    const labelColor = isDark ? '#5a6480' : '#94a3b8';
    const dotBorder = isDark ? '#050816' : '#ffffff';

    const months = data.map(d => d.month);
    const incomes = data.map(d => d.income);
    const expenses = data.map(d => d.expense);
    const maxVal = Math.max(...incomes, ...expenses, 1) * 1.2;

    const padding = { top: 30, right: 20, bottom: 40, left: 60 };
    const chartW = w - padding.left - padding.right;
    const chartH = h - padding.top - padding.bottom;

    // Grid lines
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
        const y = padding.top + (chartH / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding.left, y);
        ctx.lineTo(w - padding.right, y);
        ctx.stroke();

        // Labels
        ctx.fillStyle = labelColor;
        ctx.font = '10px Inter';
        ctx.textAlign = 'right';
        const val = maxVal - (maxVal / 4) * i;
        ctx.fillText('$' + (val / 1000).toFixed(1) + 'k', padding.left - 8, y + 3);
    }

    // X labels
    ctx.fillStyle = labelColor;
    ctx.font = '11px Inter';
    ctx.textAlign = 'center';
    months.forEach((m, i) => {
        const x = padding.left + (chartW / (months.length - 1)) * i;
        ctx.fillText(m, x, h - padding.bottom + 20);
    });

    // Draw lines
    function drawLine(values, color, fillColor) {
        ctx.beginPath();
        values.forEach((v, i) => {
            const x = padding.left + (chartW / (values.length - 1)) * i;
            const y = padding.top + chartH - (v / maxVal) * chartH;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.strokeStyle = color;
        ctx.lineWidth = 2.5;
        ctx.stroke();

        // Fill area
        const lastX = padding.left + chartW;
        const baseY = padding.top + chartH;
        ctx.lineTo(lastX, baseY);
        ctx.lineTo(padding.left, baseY);
        ctx.closePath();
        const grad = ctx.createLinearGradient(0, padding.top, 0, baseY);
        grad.addColorStop(0, fillColor);
        grad.addColorStop(1, 'transparent');
        ctx.fillStyle = grad;
        ctx.fill();

        // Dots
        values.forEach((v, i) => {
            const x = padding.left + (chartW / (values.length - 1)) * i;
            const y = padding.top + chartH - (v / maxVal) * chartH;
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
            ctx.strokeStyle = dotBorder;
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    }

    drawLine(incomes, '#10b981', 'rgba(16,185,129,0.1)');
    drawLine(expenses, '#ef4444', 'rgba(239,68,68,0.1)');

    // Legend
    ctx.font = '11px Inter';
    ctx.fillStyle = '#10b981';
    ctx.fillRect(padding.left, 8, 12, 12);
    ctx.fillStyle = '#8892b0';
    ctx.textAlign = 'left';
    ctx.fillText('Income', padding.left + 18, 18);

    ctx.fillStyle = '#ef4444';
    ctx.fillRect(padding.left + 80, 8, 12, 12);
    ctx.fillStyle = '#8892b0';
    ctx.fillText('Expenses', padding.left + 98, 18);
}

// ══════════════════════════════════════════════════════════════
// Donut Chart Renderer
// ══════════════════════════════════════════════════════════════
function renderDonutChart(canvas, data) {
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 2;
    const w = canvas.offsetWidth;
    const h = canvas.offsetHeight;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    ctx.scale(dpr, dpr);

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const gapColor = isDark ? '#050816' : '#ffffff';

    const centerX = w / 2;
    const centerY = h / 2;
    const radius = Math.min(w, h) / 2 - 10;
    const innerRadius = radius * 0.65;
    const total = data.reduce((sum, d) => sum + d.value, 0);

    let startAngle = -Math.PI / 2;

    data.forEach((item, index) => {
        const sliceAngle = (item.value / total) * (Math.PI * 2);
        const endAngle = startAngle + sliceAngle;

        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.arc(centerX, centerY, innerRadius, endAngle, startAngle, true);
        ctx.closePath();
        ctx.fillStyle = item.color;
        ctx.fill();

        // Gap between slices
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, endAngle - 0.02, endAngle + 0.02);
        ctx.arc(centerX, centerY, innerRadius, endAngle + 0.02, endAngle - 0.02, true);
        ctx.closePath();
        ctx.fillStyle = gapColor;
        ctx.fill();

        startAngle = endAngle;
    });

    // Populate legend (currency symbol from page or default)
    const legendEl = canvas.closest('.donut-chart-container')?.querySelector('.donut-legend');
    if (legendEl) {
        const symbol = window.__currencySymbol || '$';
        legendEl.innerHTML = data.map(item =>
            `<div class="donut-legend-item">
                <div class="donut-legend-color" style="background:${item.color}"></div>
                ${item.label}: ${symbol}${item.value.toLocaleString()}
            </div>`
        ).join('');
    }
}

// ══════════════════════════════════════════════════════════════
// Copy to clipboard utility
// ══════════════════════════════════════════════════════════════
window.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(() => {
        const toast = document.createElement('div');
        toast.className = 'alert alert-success';
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:1000;animation:fadeInUp 0.3s ease;box-shadow:var(--shadow-lg);';
        toast.textContent = '✓ Copied to clipboard';
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    });
};
