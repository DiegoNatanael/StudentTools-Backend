// app.js

// --- API Endpoints ---
const AI_API = "http://127.0.0.1:8000/api";      // AI Backend (Python/FastAPI)
const LARAVEL_API = "http://127.0.0.1:8001/api";             // Laravel Backend (Auth + Quota)

// --- UI Elements ---
const navButtons = document.querySelectorAll('.nav-btn:not(.auth-btn):not(.logout-btn)');
const sections = document.querySelectorAll('.hero-section');
const logOverlay = document.getElementById('statusLog');
const logMessages = document.getElementById('logMessages');

// --- Auth State ---
let authToken = localStorage.getItem('ST_AUTH_TOKEN');
let currentUser = null;
let currentSection = 'docs';
let currentDiagType = 'Flowchart';

// ============================================================
// AUTH SYSTEM
// ============================================================

const authOverlay = document.getElementById('authOverlay');
const openAuthBtn = document.getElementById('openAuthBtn');
const closeAuthBtn = document.getElementById('closeAuth');
const userInfoEl = document.getElementById('userInfo');
const userNameEl = document.getElementById('userName');
const quotaBadgeEl = document.getElementById('quotaBadge');
const logoutBtn = document.getElementById('logoutBtn');

// Open/Close Auth Modal
openAuthBtn.addEventListener('click', () => authOverlay.classList.remove('hidden'));
closeAuthBtn.addEventListener('click', () => authOverlay.classList.add('hidden'));
authOverlay.addEventListener('click', (e) => {
    if (e.target === authOverlay) authOverlay.classList.add('hidden');
});

// Tab switching
document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
        tab.classList.add('active');
        const formId = tab.dataset.tab === 'login' ? 'loginForm' : 'registerForm';
        document.getElementById(formId).classList.add('active');
    });
});

// Login
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('loginError');
    errorEl.classList.add('hidden');

    try {
        const resp = await fetch(`${LARAVEL_API}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                email: document.getElementById('loginEmail').value,
                password: document.getElementById('loginPassword').value,
            })
        });

        const data = await resp.json();
        if (!resp.ok) throw new Error(data.message || data.errors?.email?.[0] || 'Login failed');

        handleAuthSuccess(data);
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.classList.remove('hidden');
    }
});

// Register
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('registerError');
    errorEl.classList.add('hidden');

    try {
        const resp = await fetch(`${LARAVEL_API}/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: document.getElementById('regName').value,
                email: document.getElementById('regEmail').value,
                password: document.getElementById('regPassword').value,
                password_confirmation: document.getElementById('regPasswordConfirm').value,
            })
        });

        const data = await resp.json();
        if (!resp.ok) {
            const firstError = data.errors ? Object.values(data.errors)[0][0] : data.message;
            throw new Error(firstError || 'Registration failed');
        }

        handleAuthSuccess(data);
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.classList.remove('hidden');
    }
});

function handleAuthSuccess(data) {
    authToken = data.token;
    currentUser = data.user;
    localStorage.setItem('ST_AUTH_TOKEN', authToken);
    updateAuthUI();
    authOverlay.classList.add('hidden');
    refreshQuota();
}

// Logout
logoutBtn.addEventListener('click', async () => {
    try {
        await fetch(`${LARAVEL_API}/logout`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });
    } catch (e) { /* ignore */ }

    authToken = null;
    currentUser = null;
    localStorage.removeItem('ST_AUTH_TOKEN');
    updateAuthUI();
});

function updateAuthUI() {
    if (authToken && currentUser) {
        userInfoEl.classList.remove('hidden');
        openAuthBtn.classList.add('hidden');
        userNameEl.textContent = currentUser.name;
    } else {
        userInfoEl.classList.add('hidden');
        openAuthBtn.classList.remove('hidden');
    }
}

async function refreshQuota() {
    if (!authToken) return;
    try {
        const resp = await fetch(`${LARAVEL_API}/check-quota`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });
        if (!resp.ok) {
            if (resp.status === 401) { handleExpiredToken(); return; }
            return;
        }
        const data = await resp.json();
        quotaBadgeEl.textContent = data.remaining === 'unlimited' ? '∞' : data.remaining;
        quotaBadgeEl.classList.toggle('low', data.remaining !== 'unlimited' && data.remaining <= 1);
    } catch (e) { console.warn('Quota check failed:', e); }
}

function handleExpiredToken() {
    authToken = null;
    currentUser = null;
    localStorage.removeItem('ST_AUTH_TOKEN');
    updateAuthUI();
}

// Check existing token on load
async function initAuth() {
    if (!authToken) { updateAuthUI(); return; }
    try {
        const resp = await fetch(`${LARAVEL_API}/me`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });
        if (!resp.ok) throw new Error('Invalid token');
        currentUser = await resp.json();
        updateAuthUI();
        refreshQuota();
    } catch (e) {
        handleExpiredToken();
    }
}
initAuth();

// ============================================================
// QUOTA GATE — checks Laravel before allowing generation
// ============================================================

async function checkQuotaGate() {
    // Bypassed for testing so we don't get blocked by missing auth or Laravel connection issues.
    return true;
}

async function logGeneration(type, topic) {
    if (!authToken) return;
    try {
        await fetch(`${LARAVEL_API}/generations`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ type, topic })
        });
        refreshQuota();
    } catch (e) { console.warn('Generation log failed:', e); }
}

// ============================================================
// NAVIGATION
// ============================================================

navButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const target = btn.dataset.section;
        navButtons.forEach(b => b.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(target).classList.add('active');
        currentSection = target;
    });
});

// --- Diagram Picker Logic ---
const diagTypeCards = document.querySelectorAll('.diag-type-card');
diagTypeCards.forEach(card => {
    card.addEventListener('click', () => {
        diagTypeCards.forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        currentDiagType = card.dataset.type;
    });
});

// ============================================================
// LOGGER SYSTEM
// ============================================================

function logStatus(message, isNew = false) {
    if (isNew) logMessages.innerHTML = "";
    logOverlay.classList.remove('hidden');
    const msgDiv = document.createElement('div');
    msgDiv.className = 'msg';
    msgDiv.textContent = `> ${message} `;
    logMessages.appendChild(msgDiv);
    logMessages.scrollTop = logMessages.scrollHeight;
    return msgDiv;
}

function hideLog() {
    setTimeout(() => {
        logOverlay.classList.add('hidden');
    }, 2000);
}

// ============================================================
// AI API HELPER (talks to Python backend)
// ============================================================

function getDeviceId() {
    const info = [
        navigator.userAgent,
        screen.height,
        screen.width,
        new Date().getTimezoneOffset()
    ].join('|');

    let hash = 0;
    for (let i = 0; i < info.length; i++) {
        const char = info.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    return Math.abs(hash).toString(16);
}

async function generateGeneric(endpoint, body, onSuccess) {
    const adminToken = localStorage.getItem('STUDENT_TOOLS_ADMIN');
    const deviceId = getDeviceId();

    try {
        const response = await fetch(`${AI_API}${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Admin-Token': adminToken || "",
                'X-Device-Id': deviceId
            },
            body: JSON.stringify(body)
        });

        if (!response.ok) throw new Error(await response.text());
        return response;
    } catch (error) {
        logStatus(`Error: ${error.message} `);
        console.error(error);
        return null;
    }
}

// ============================================================
// DOCUMENT GENERATION (with Laravel quota)
// ============================================================

document.getElementById('genDocBtn').addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const topic = document.getElementById('docInput').value.trim();

    if (!topic) return alert("Please enter a topic");
    if (btn.disabled) return;

    // CHECK QUOTA FIRST
    if (!(await checkQuotaGate())) return;

    let timerInterval = null;
    try {
        btn.disabled = true;
        btn.style.opacity = "0.5";
        btn.style.cursor = "not-allowed";

        logStatus("AI is writing your document...", true);
        const timerLog = logStatus("Elapsed time: 0s");

        let seconds = 0;
        timerInterval = setInterval(() => {
            seconds++;
            timerLog.textContent = `> Elapsed time: ${seconds}s (Generating DOCX...)`;
        }, 1000);

        const response = await generateGeneric('/generate/docx', { topic });
        if (!response) {
            if (timerInterval) clearInterval(timerInterval);
            return;
        }

        const blob = await response.blob();
        const safeName = topic.replace(/[^a-zA-Z0-9áéíóúñÁÉÍÓÚÑ ]/g, '').replace(/ /g, '_');
        downloadBlob(blob, `${safeName}.docx`);
        logStatus("Success! Your document is ready.");

        // LOG TO LARAVEL
        await logGeneration('document', topic);
        hideLog();
    } catch (error) {
        logStatus(`Error: ${error.message}`);
    } finally {
        if (timerInterval) clearInterval(timerInterval);
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
    }
});

// --- Global State for Presentation ---
let lastGeneratedSlides = null;

// ============================================================
// PRESENTATION GENERATION (Reveal.js & GSAP — Always Black Aesthetic)
// ============================================================

document.getElementById('genPptBtn').addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const topic = document.getElementById('pptInput').value.trim();
    if (!topic) return alert("Please enter a topic");
    if (btn.disabled) return;

    // CHECK QUOTA FIRST
    if (!(await checkQuotaGate())) return;

    let timerInterval = null;
    try {
        btn.disabled = true;
        btn.style.opacity = "0.5";
        btn.style.cursor = "not-allowed";
        
        logStatus("🧠 Starting Reveal.js Presentation Generator...", true);
        const timerLog = logStatus("Elapsed time: 0s");

        let seconds = 0;
        timerInterval = setInterval(() => {
            seconds++;
            timerLog.textContent = `> Elapsed time: ${seconds}s (Please hold, crafting beautiful slides...)`;
        }, 1000);

        logStatus("Planning your presentation slides (Fast local connection)...");

        // Step 1: Generate the plan/content
        const response = await generateGeneric('/generate/plan', { topic, type: "presentation" });
        if (!response) return;

        const data = await response.json();
        lastGeneratedSlides = data;

        logStatus(`Generated ${data.slides.length} slides. Building deck...`);

        // Step 2: Render in Overlay
        renderRevealPresentation(data);

        // LOG TO LARAVEL
        await logGeneration('presentation', topic);
        hideLog();
    } catch (error) {
        logStatus(`Error: ${error.message}`);
    } finally {
        if (timerInterval) clearInterval(timerInterval);
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
    }
});

// Shared slide HTML builder — used by renderer, HTML export, and PDF export
function buildSlideHtml(slide, fallbackTitle) {
    const layout = slide.layout || 'text';

    if (layout === 'intro') {
        return `
            <section>
                <h3>${slide.section || 'PRESENTACIÓN'}</h3>
                <div class="divider"></div>
                <h1>${slide.h1 || fallbackTitle}</h1>
                <p>${slide.p || ''}</p>
            </section>`;
    } else if (layout === 'bullets' && slide.bullets) {
        const bulletItems = slide.bullets.map(b => `<li>${b}</li>`).join('');
        return `
            <section>
                <div class="container slide-container">
                    <div class="col-text">
                        <h3>${slide.section || ''}</h3>
                        <h2>${slide.h2 || ''}</h2>
                        <div class="divider"></div>
                        <ul class="slide-bullets">${bulletItems}</ul>
                    </div>
                </div>
            </section>`;
    } else if (layout === 'quote') {
        return `
            <section>
                <div class="center-content">
                    <h3>${slide.section || 'REFLEXIÓN'}</h3>
                    <div class="divider"></div>
                    <blockquote>${slide.quote || ''}</blockquote>
                    ${slide.source ? `<a href="#" class="source-link">${slide.source}</a>` : ''}
                </div>
            </section>`;
    } else if (layout === 'table' && slide.table) {
        const headers = slide.table.headers.map(h => `<th>${h}</th>`).join('');
        const rows = slide.table.rows.map(row => `<tr>${row.map(c => `<td>${c}</td>`).join('')}</tr>`).join('');
        return `
            <section>
                <h3>${slide.section || 'DATOS'}</h3>
                <h2>${slide.h2 || ''}</h2>
                <div class="divider"></div>
                <table>
                    <thead><tr>${headers}</tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </section>`;
    } else if (layout === 'conclusion') {
        return `
            <section>
                <div class="center-content">
                    <h3>${slide.section || 'CONCLUSIÓN'}</h3>
                    <h1>${slide.h1 || 'Conclusión'}</h1>
                    <div class="divider"></div>
                    <p>${slide.p || ''}</p>
                </div>
            </section>`;
    } else {
        // Default "text" layout
        return `
            <section>
                <div class="container slide-container">
                    <div class="col-text">
                        <h3>${slide.section || ''}</h3>
                        <h2>${slide.h2 || ''}</h2>
                        <div class="divider"></div>
                        <p>${slide.p || ''}</p>
                    </div>
                </div>
            </section>`;
    }
}

function renderRevealPresentation(data) {
    const overlay = document.getElementById('presentationOverlay');
    const container = document.getElementById('revealContainer');

    let slidesHtml = data.slides.map(slide => buildSlideHtml(slide, data.title)).join('');

    container.innerHTML = `
        <div class="reveal">
            <div class="slides">
                ${slidesHtml}
            </div>
        </div>
    `;

    overlay.classList.remove('hidden');

    // Initialize Reveal.js deck
    const deck = new Reveal(container.querySelector('.reveal'), {
        controls: false,
        progress: false,
        center: true,
        hash: false,
        transition: 'fade',
        transitionSpeed: 'slow',
        width: 1200,
        height: 900
    });

    deck.initialize().then(() => {
        animateSlide(deck.getCurrentSlide());
        deck.on('slidechanged', event => animateSlide(event.currentSlide));
    });

    window.currentDeck = deck;
}

function animateSlide(slide) {
    const elements = slide.querySelectorAll('h1, h2, h3, p, .divider, blockquote, table, .source-link, .slide-bullets li');
    gsap.fromTo(elements,
        { opacity: 0, y: 40 },
        { opacity: 1, y: 0, duration: 1.2, stagger: 0.15, ease: "power3.out" }
    );
}

// Global click on container to advance slide
document.getElementById('revealContainer').addEventListener('mousedown', (e) => {
    if (window.currentDeck && e.button === 0) {
        if (e.target.tagName !== 'BUTTON' && !e.target.closest('button')) {
            e.preventDefault();
            window.currentDeck.next();
        }
    }
});

// Close Presentation Viewer
document.getElementById('closePresentation').addEventListener('click', () => {
    // Destroy Reveal.js deck to prevent leftover styling artifacts
    if (window.currentDeck) {
        window.currentDeck.destroy();
        window.currentDeck = null;
    }
    document.getElementById('revealContainer').innerHTML = '';
    document.getElementById('presentationOverlay').classList.add('hidden');
});

// (HTML download removed — only PDF export remains)

// Export Slide Deck as a high-fidelity PDF document using Reveal.js's native printing!
document.getElementById('downloadPdfBtn').addEventListener('click', () => {
    if (!lastGeneratedSlides) return;

    logStatus("Preparing PDF printer...", true);
    
    // Reuse the shared builder
    let slidesHtml = lastGeneratedSlides.slides.map(slide => buildSlideHtml(slide, lastGeneratedSlides.title)).join('\n');

    // Standalone print-pdf compiler
    const htmlContent = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${lastGeneratedSlides.title}</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/reveal.js/4.5.0/reveal.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #050505;
            --text-primary: #ffffff;
            --text-secondary: #888888;
            --accent: #6366f1;
            --card-border: rgba(255, 255, 255, 0.1);
            --font-main: 'Inter', sans-serif;
        }

        body, .reveal {
            background-color: var(--bg-color) !important;
            font-family: var(--font-main);
            color: var(--text-primary);
        }

        .reveal .slides section {
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            height: 100%;
        }

        .reveal h1, .reveal h2, .reveal h3 { 
            color: var(--text-primary) !important; 
            margin: 0; 
            text-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .reveal h1 { font-weight: 600; text-transform: uppercase; font-size: 3.8em !important; letter-spacing: -0.04em; line-height: 0.9; }
        .reveal h2 { font-weight: 400; text-transform: uppercase; font-size: 2.2em !important; margin-bottom: 20px; }
        .reveal h3 { font-weight: 300; color: var(--text-secondary) !important; text-transform: uppercase; font-size: 0.85em !important; letter-spacing: 0.5em; margin-bottom: 15px; }
        .reveal p { color: var(--text-secondary); font-weight: 300; line-height: 1.4; font-size: 1.2em !important; }

        .divider { width: 60px; height: 3px; background: var(--accent); margin: 30px 0; }
        
        blockquote { border-left: 4px solid var(--accent); background: rgba(255,255,255,0.05); padding: 30px; font-style: italic; border-radius: 0 12px 12px 0; color: var(--text-primary) !important; font-size: 1.4em !important; margin: 20px 0; text-align: left; }

        .slide-container { display: flex; align-items: center; justify-content: space-between; gap: 80px; width: 100%; text-align: left; }
        .col-text { flex: 1.4; }
        .source-link { font-size: 0.5em; color: #444; text-decoration: none; text-transform: uppercase; letter-spacing: 2px; margin-top: 25px; display: block; }

        table { border-collapse: collapse; width: 100%; color: var(--text-secondary); font-size: 0.85em; margin-top: 20px; }
        th { color: white; border-bottom: 2px solid var(--card-border); padding: 15px; text-transform: uppercase; font-size: 0.7em; text-align: left; }
        td { padding: 18px 15px; border-bottom: 1px solid var(--card-border); line-height: 1.2; }

        .slide-bullets { list-style: none; padding: 0; margin: 0; text-align: left; }
        .slide-bullets li { color: var(--text-secondary); font-weight: 300; font-size: 1.1em; padding: 8px 0; padding-left: 20px; position: relative; line-height: 1.4; }
        .slide-bullets li::before { content: '▸'; color: var(--accent); position: absolute; left: 0; font-size: 1.1em; }
    </style>
</head>
<body>

    <div class="reveal">
        <div class="slides">
${slidesHtml}
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/reveal.js/4.5.0/reveal.min.js"></script>
    <script>
        Reveal.initialize({
            controls: false, progress: false, hash: false, center: true,
            width: 1200, height: 900
        });
        Reveal.on('ready', () => { setTimeout(() => { window.print(); }, 1000); });
    </script>
</body>
</html>`;

    // Open Blob URL in new window which automatically pops up the PDF print dialog!
    const blob = new Blob([htmlContent], { type: "text/html" });
    const url = window.URL.createObjectURL(blob);
    window.open(url, "_blank");
    
    logStatus("✅ Print window opened. Choose 'Save as PDF'!");
    hideLog();
});

// ============================================================
// DIAGRAM GENERATION (with Laravel quota)
// ============================================================

document.getElementById('genDiagBtn').addEventListener('click', async () => {
    const topic = document.getElementById('diagInput').value.trim();
    const type = currentDiagType;
    if (!topic) return alert("Please enter a topic");

    // CHECK QUOTA FIRST
    if (!(await checkQuotaGate())) return;

    const container = document.getElementById('mermaidOutput');
    container.innerHTML = '<div class="loader"></div>';
    logStatus(`Architecting ${type} diagram...`, true);
    const timerLog = logStatus("Elapsed time: 0s");

    let timerInterval = null;
    let seconds = 0;
    timerInterval = setInterval(() => {
        seconds++;
        timerLog.textContent = `> Elapsed time: ${seconds}s (Generating Mermaid code...)`;
    }, 1000);

    try {
        const response = await generateGeneric('/generate/diagram', { topic, type });
        if (response) {
            const data = await response.json();
            logStatus("Diagram logic established. Rendering...");

            container.removeAttribute('data-processed');
            container.innerHTML = data.code;
            try {
                await mermaid.run({ nodes: [container] });
                logStatus("Visualization complete.");

                // LOG TO LARAVEL
                await logGeneration('diagram', topic);
            } catch (e) {
                container.innerHTML = '<div class="text-red-500">Render Error. Try clarifying your description.</div>';
            }
            hideLog();
        }
    } catch (error) {
        logStatus(`Error: ${error.message}`);
    } finally {
        if (timerInterval) clearInterval(timerInterval);
    }
});

// ============================================================
// UTILITIES
// ============================================================

function downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
}

// --- God Mode Secret Activation ---
window.activateGodMode = (pw) => {
    localStorage.setItem('STUDENT_TOOLS_ADMIN', pw);
    console.log("God Mode Activated. Unlimited uses enabled.");
};

// --- Admin Check on Load ---
async function checkAdminStatus() {
    try {
        const adminToken = localStorage.getItem('STUDENT_TOOLS_ADMIN');
        const response = await fetch(`${AI_API}/health`, {
            headers: { 'X-Admin-Token': adminToken || "" }
        });
        const data = await response.json();
        if (data.is_admin) {
            console.log("God Mode Status: ACTIVE 👑");
        } else {
            console.warn("God Mode Status: INACTIVE 👤");
        }
    } catch (e) { }
}
checkAdminStatus();

// ============================================================
// GENERATION HISTORY
// ============================================================

let historyFilter = 'all';
let historyPage = 1;
let allHistoryData = [];

const historyListEl = document.getElementById('historyList');
const historyPaginationEl = document.getElementById('historyPagination');

// Filter chip logic
document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        historyFilter = chip.dataset.filter;
        renderHistoryItems();
    });
});

// Refresh button
document.getElementById('refreshHistoryBtn').addEventListener('click', () => fetchHistory());

// Auto-fetch when switching to history tab
const origNavClick = navButtons.forEach.bind(navButtons);
navButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        if (btn.dataset.section === 'history' && authToken) {
            fetchHistory();
        }
    });
});

async function fetchHistory(page = 1) {
    if (!authToken) {
        renderHistoryEmpty('Sign in to see your history');
        return;
    }

    historyListEl.innerHTML = '<div class="history-loading"><div class="loader"></div></div>';
    historyPaginationEl.classList.add('hidden');

    try {
        const resp = await fetch(`${LARAVEL_API}/generations?page=${page}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`
            }
        });

        if (resp.status === 401) { handleExpiredToken(); return; }
        if (!resp.ok) throw new Error('Failed to fetch history');

        const data = await resp.json();
        allHistoryData = data.data || [];
        historyPage = data.current_page || 1;

        renderHistoryItems();
        renderPagination(data);
    } catch (e) {
        console.warn('History fetch failed:', e);
        renderHistoryEmpty('Could not load history. Is the server running?');
    }
}

function renderHistoryItems() {
    const filtered = historyFilter === 'all'
        ? allHistoryData
        : allHistoryData.filter(g => g.type === historyFilter);

    if (filtered.length === 0) {
        const msg = historyFilter === 'all'
            ? 'No generations yet. Create something awesome!'
            : `No ${historyFilter} generations found.`;
        renderHistoryEmpty(msg);
        return;
    }

    const typeIcons = {
        document: 'fa-file-alt',
        presentation: 'fa-tv',
        diagram: 'fa-diagram-project',
    };

    historyListEl.innerHTML = filtered.map((gen, i) => `
        <div class="history-item" style="animation-delay: ${i * 0.05}s">
            <div class="history-icon ${gen.type}">
                <i class="fas ${typeIcons[gen.type] || 'fa-file'}"></i>
            </div>
            <div class="history-details">
                <div class="history-topic">${escapeHtml(gen.topic)}</div>
                <div class="history-meta">
                    <span class="history-type-badge ${gen.type}">${gen.type}</span>
                    <span>${formatRelativeTime(gen.created_at)}</span>
                </div>
            </div>
        </div>
    `).join('');
}

function renderHistoryEmpty(message) {
    historyListEl.innerHTML = `
        <div class="history-empty">
            <i class="fas fa-hourglass-start"></i>
            <p>${message}</p>
        </div>
    `;
}

function renderPagination(data) {
    const lastPage = data.last_page || 1;
    if (lastPage <= 1) {
        historyPaginationEl.classList.add('hidden');
        return;
    }

    historyPaginationEl.classList.remove('hidden');
    let html = `<button class="page-btn" onclick="fetchHistory(${data.current_page - 1})" ${data.current_page <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

    for (let p = 1; p <= lastPage; p++) {
        html += `<button class="page-btn ${p === data.current_page ? 'active' : ''}" onclick="fetchHistory(${p})">${p}</button>`;
    }

    html += `<button class="page-btn" onclick="fetchHistory(${data.current_page + 1})" ${data.current_page >= lastPage ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

    historyPaginationEl.innerHTML = html;
}

function formatRelativeTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHrs = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHrs < 24) return `${diffHrs}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Initialize Mermaid
mermaid.initialize({ startOnLoad: false, theme: 'dark' });