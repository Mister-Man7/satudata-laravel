import Chart from 'chart.js/auto';
import {marked} from "marked";

const mobileMenuButton = document.querySelector('[data-mobile-menu-button]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

window.Chart = Chart;
window.marked = marked;
window.initStatistikMahasiswaCharts = function (ChartLibrary) {
    const root = document.querySelector('[data-statistik-mahasiswa-root]');
    if (!root) return;

    const payload = JSON.parse(root.getAttribute('data-payload'));
    const chartData = payload.chartCatalog;
    const canvas = root.querySelector('[data-statistik-mahasiswa-chart]');
    const ctx = canvas?.getContext('2d');

    if (!ctx || !chartData) return;

    // 1. Palet Warna Gradasi Modern (Vibrant & Colorful)
    const colorPalettes = [
        {top: '#3b82f6', bottom: '#1d4ed8'}, // Blue
        {top: '#10b981', bottom: '#047857'}, // Emerald
        {top: '#8b5cf6', bottom: '#5b21b6'}, // Violet
        {top: '#f59e0b', bottom: '#b45309'}, // Amber
        {top: '#ec4899', bottom: '#be185d'}, // Pink
        {top: '#06b6d4', bottom: '#0e7490'}, // Cyan
        {top: '#f43f5e', bottom: '#be123c'}, // Rose
        {top: '#6366f1', bottom: '#4338ca'}, // Indigo
    ];

    // Build gradasi unik untuk masing-masing batang fakultas
    const backgrounds = chartData.labels.map((_, i) => {
        const palette = colorPalettes[i % colorPalettes.length];
        const gradient = ctx.createLinearGradient(0, 0, 0, 350);
        gradient.addColorStop(0, palette.top);
        gradient.addColorStop(1, palette.bottom);
        return gradient;
    });

    // 2. Plugin Custom: Drop Shadow Halus Ala CSS
    const shadowPlugin = {
        id: 'customShadow',
        beforeDatasetDraw: (chart) => {
            const {ctx} = chart;
            ctx.save();
            ctx.shadowColor = 'rgba(15, 23, 42, 0.12)'; // Slate 900 dengan opacity rendah
            ctx.shadowBlur = 12;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 6;
        },
        afterDatasetDraw: (chart) => {
            chart.ctx.restore();
        }
    };

    new ChartLibrary(ctx, {
        type: 'bar',
        plugins: [shadowPlugin], // Aktifkan efek bayangan
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Mahasiswa Aktif',
                data: chartData.data,
                backgroundColor: backgrounds,
                borderRadius: 0,          // Sudut membulat modern
                borderSkipped: false,      // Semua sudut mulus
                maxBarThickness: 46,       // Batas maksimal lebar batang biar nggak kegendutan
                barPercentage: 0.7,
                categoryPercentage: 0.8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1200,
                easing: 'easeOutQuart', // Animasi naik yang mulus elegan
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {display: false},
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(15, 23, 42, 0.92)', // Slate dark glass effect
                    titleFont: {size: 12, weight: '600', family: "system-ui, -apple-system, sans-serif"},
                    bodyFont: {size: 14, weight: '800', family: "system-ui, -apple-system, sans-serif"},
                    padding: {top: 10, right: 14, bottom: 10, left: 14},
                    cornerRadius: 10,
                    displayColors: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    usePointStyle: true,
                    callbacks: {
                        label: (context) => `  ${context.parsed.y.toLocaleString('id-ID')} Mahasiswa`
                    }
                }
            },
            scales: {
                x: {
                    grid: {display: false, drawBorder: false},
                    ticks: {
                        font: {size: 12, weight: '600'},
                        color: '#475569', // Slate 600
                        padding: 8
                    }
                },
                y: {
                    beginAtZero: true,
                    border: {dash: [5, 5], display: false}, // Garis putus-putus (dashed)
                    grid: {
                        color: '#f1f5f9', // Slate 100 super halus
                        drawBorder: false,
                    },
                    ticks: {
                        font: {size: 11, weight: '500'},
                        color: '#94a3b8', // Slate 400
                        padding: 10,
                        callback: (value) => value.toLocaleString('id-ID')
                    }
                }
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initStatistikMahasiswaCharts === 'function') {
        window.initStatistikMahasiswaCharts(Chart);
    }

    initTirtaAgentDrawer();
    initTirtaAgentChats();
});

if (mobileMenuButton && mobileMenu) {
    const openIcon = mobileMenuButton.querySelector('[data-menu-open-icon]');
    const closeIcon = mobileMenuButton.querySelector('[data-menu-close-icon]');

    const closeMobileMenu = () => {
        mobileMenu.classList.add('hidden');
        mobileMenuButton.setAttribute('aria-expanded', 'false');
        openIcon?.classList.remove('hidden');
        closeIcon?.classList.add('hidden');
    };

    mobileMenuButton.addEventListener('click', () => {
        const isOpen = mobileMenuButton.getAttribute('aria-expanded') === 'true';

        mobileMenu.classList.toggle('hidden', isOpen);
        mobileMenuButton.setAttribute('aria-expanded', String(!isOpen));
        openIcon?.classList.toggle('hidden', !isOpen);
        closeIcon?.classList.toggle('hidden', isOpen);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            closeMobileMenu();
        }
    });
}

function initTirtaAgentDrawer() {
    const shell = document.querySelector('[data-tirta-chat-shell]');
    const panel = document.querySelector('[data-tirta-chat-panel]');
    const input = document.querySelector('[data-tirta-input]');
    const openButtons = document.querySelectorAll('[data-tirta-chat-open]');
    const closeButtons = document.querySelectorAll('[data-tirta-chat-close]');

    if (!shell || !panel || openButtons.length === 0) {
        return;
    }

    const openDrawer = () => {
        shell.classList.remove('hidden');
        shell.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => {
            panel.classList.remove('translate-x-full');
            input?.focus();
        });
    };

    const closeDrawer = () => {
        panel.classList.add('translate-x-full');
        shell.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        window.setTimeout(() => {
            shell.classList.add('hidden');
        }, 200);
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', openDrawer);
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.getAttribute('aria-hidden') === 'false') {
            closeDrawer();
        }
    });
}

function initTirtaAgentChats() {
    document.querySelectorAll('[data-tirta-chat]').forEach((chat) => {
        const form = chat.querySelector('[data-tirta-form]');
        const input = chat.querySelector('[data-tirta-input]');
        const messages = chat.querySelector('[data-tirta-messages]');
        const submit = chat.querySelector('[data-tirta-submit]');
        const status = chat.querySelector('[data-tirta-status]');
        const counter = chat.querySelector('[data-tirta-counter]');
        const typing = chat.querySelector('[data-tirta-typing]');
        const promptButtons = chat.querySelectorAll('[data-tirta-prompt]');
        const resetButtons = document.querySelectorAll('[data-tirta-chat-reset]');
        let conversationId = window.sessionStorage.getItem('tirta_agent_conversation_id');

        if (!form || !input || !messages || !submit || !status) {
            return;
        }
        const sendIcon = submit.querySelector('[data-send-icon]');
        const sendSpinner = submit.querySelector('[data-send-spinner]');

        const renderMarkdown = (content) => {
            console.log(window.marked);
            if (window.marked && typeof window.marked.parse === 'function') {
                try {
                    const html = window.marked.parse(content);
                    return window.DOMPurify ? DOMPurify.sanitize(html) : html;
                } catch (e) {
                    console.error('Failed parsing markdown:', e);
                }
            }
            return content
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
        };

        const scrollToBottom = () => {
            messages.scrollTo({
                top: messages.scrollHeight,
                behavior: 'smooth'
            });
        };

        const autosizeInput = () => {
            input.style.height = 'auto';
            input.style.height = `${Math.min(input.scrollHeight, 128)}px`;

            if (counter) {
                counter.textContent = `${input.value.length}/1000`;
            }
        };

        const addMessage = (content, role) => {
            typing?.classList.add('hidden');
            typing?.classList.remove('flex');

            const row = document.createElement('div');
            const bubble = document.createElement('div');
            const avatar = document.createElement('div');

            row.className = role === 'user'
                ? 'flex justify-end'
                : 'flex items-start gap-3';

            if (role === 'assistant') {
                avatar.className = 'mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-700';
                avatar.innerHTML = '<i class="fa-solid fa-robot text-sm"></i>';
                row.appendChild(avatar);
            }

            bubble.className = role === 'user'
                ? 'max-w-[86%] rounded-2xl rounded-tr-md bg-cyan-600 p-4 text-sm leading-6 text-white shadow-sm'
                : 'max-w-[86%] rounded-2xl rounded-tl-md bg-white p-4 text-sm leading-6 text-slate-700 shadow-sm ring-1 ring-slate-200 tirta-markdown-content';

            if (role === 'assistant') {
                bubble.innerHTML = renderMarkdown(content);
                // Make links open in a new tab
                bubble.querySelectorAll('a').forEach((a) => {
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                });
            } else {
                bubble.textContent = content;
            }

            row.appendChild(bubble);
            const time = document.createElement('div');

            time.className =
                'mt-2 text-[11px] opacity-70';

            time.textContent = new Date().toLocaleTimeString(
                'id-ID',
                {
                    hour: '2-digit',
                    minute: '2-digit'
                }
            );

            bubble.appendChild(time);
            messages.appendChild(row);
            scrollToBottom();
        };

        const setLoading = (isLoading) => {

            submit.disabled = isLoading;
            input.disabled = isLoading;
            status.textContent = isLoading ? 'TirtaAgent sedang menjawab...' : '';

            if (typing) {
                typing.classList.toggle('hidden', !isLoading);
                typing.classList.toggle('flex', isLoading);
                scrollToBottom();
            }

            sendIcon?.classList.toggle(
                'hidden',
                isLoading
            );

            sendSpinner?.classList.toggle(
                'hidden',
                !isLoading
            );
        };

        const resetConversation = () => {
            conversationId = null;
            window.sessionStorage.removeItem('tirta_agent_conversation_id');
            messages.querySelectorAll('[data-tirta-welcome] ~ div:not([data-tirta-typing])').forEach((message) => {
                message.remove();
            });
            status.textContent = 'Percakapan baru siap.';
            input.value = '';
            autosizeInput();
            input.focus();
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const message = input.value.trim();

            if (!message) {
                return;
            }

            addMessage(message, 'user');
            input.value = '';
            autosizeInput();
            setLoading(true);

            try {
                const response = await fetch(chat.dataset.endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': chat.dataset.csrf,
                    },
                    body: JSON.stringify({
                        message,
                        conversation_id: conversationId,
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message ?? 'TirtaAgent belum bisa menjawab.');
                }

                conversationId = payload.conversation_id;

                if (conversationId) {
                    window.sessionStorage.setItem('tirta_agent_conversation_id', conversationId);
                }

                addMessage(payload.message, 'assistant');
            } catch (error) {
                addMessage(error.message, 'assistant');
            } finally {
                setLoading(false);
                input.focus();
            }
        });

        input.addEventListener('input', autosizeInput);

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        promptButtons.forEach((button) => {
            button.addEventListener('click', () => {
                input.value = button.dataset.tirtaPrompt ?? '';
                autosizeInput();
                input.focus();
            });
        });

        resetButtons.forEach((button) => {
            button.addEventListener('click', resetConversation);
        });

        autosizeInput();
    });
}
