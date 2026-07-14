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

    if (!ctx || !chartData || !chartData.datasets) return;

    const originalLabels = [...chartData.labels];
    const originalDatasets = chartData.datasets.map(ds => ({
        ...ds,
        data: [...ds.data]
    }));

    const rawProdi = chartData.raw_prodi || [];
    const daftarJenjang = ['Diploma 3', 'Sarjana', 'Profesi', 'Magister', 'Doktor'];

    const jenjangColors = {
        'Mahasiswa Diploma 3': {bg: '#0f766e', text: '#ffffff'},
        'Mahasiswa Sarjana': {bg: '#10b981', text: '#ffffff'},
        'Mahasiswa Profesi': {bg: '#14b8a6', text: '#ffffff'},
        'Mahasiswa Magister': {bg: '#6ee7b7', text: '#0f172a'},
        'Mahasiswa Doktor': {bg: '#a7f3d0', text: '#0f172a'},
    };

    const styledDatasets = originalDatasets.map(ds => {
        const color = jenjangColors[ds.label] || {bg: '#3b82f6', text: '#ffffff'};
        return {
            label: ds.label,
            data: ds.data,
            backgroundColor: color.bg,
            borderColor: '#ffffff',
            borderWidth: 1,
            borderRadius: 2,
            maxBarThickness: 48,
        };
    });

    const stackedBarNumbersPlugin = {
        id: 'stackedBarNumbers',
        afterDatasetsDraw(chart) {
            const {ctx, data, scales: {x}} = chart;
            ctx.save();
            ctx.textAlign = 'center';

            ctx.font = '600 11px sans-serif';
            ctx.textBaseline = 'middle';
            chart.data.datasets.forEach((dataset, dsIndex) => {
                const meta = chart.getDatasetMeta(dsIndex);
                if (meta.hidden) return;

                meta.data.forEach((bar, index) => {
                    const value = dataset.data[index];
                    if (!value || value === 0) return;

                    const height = Math.abs(bar.base - bar.y);
                    if (height > 18) {
                        const colorConfig = jenjangColors[dataset.label] || {text: '#ffffff'};
                        ctx.fillStyle = colorConfig.text;
                        ctx.fillText(value.toLocaleString('id-ID'), bar.x, bar.y + (height / 2));
                    }
                });
            });

            ctx.font = '800 12px sans-serif';
            ctx.fillStyle = '#0f172a';
            ctx.textBaseline = 'bottom';

            for (let i = 0; i < data.labels.length; i++) {
                let total = 0;
                let minTopY = chart.chartArea.bottom;

                chart.data.datasets.forEach((dataset, dsIdx) => {
                    const meta = chart.getDatasetMeta(dsIdx);
                    if (!meta.hidden) {
                        const val = dataset.data[i] || 0;
                        total += val;
                        const bar = meta.data[i];
                        if (bar && val > 0 && bar.y < minTopY) {
                            minTopY = bar.y;
                        }
                    }
                });

                if (total > 0 && minTopY < chart.chartArea.bottom) {
                    ctx.fillText(total.toLocaleString('id-ID'), x.getPixelForValue(i), minTopY - 5);
                }
            }
            ctx.restore();
        }
    };

    const myChart = new ChartLibrary(ctx, {
        type: 'bar',
        plugins: [stackedBarNumbersPlugin],
        data: {
            labels: originalLabels,
            datasets: styledDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {padding: {top: 30}},
            animation: {duration: 1000, easing: 'easeOutQuart'},
            interaction: {mode: 'index', intersect: false},
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        font: {size: 11, weight: '600'},
                        color: '#475569',
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.92)',
                    titleFont: {size: 13, weight: 'bold'},
                    bodyFont: {size: 13},
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: (context) => `  ${context.dataset.label}: ${context.parsed.y.toLocaleString('id-ID')}`
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: {display: false, drawBorder: false},
                    ticks: {
                        font: {size: 11, weight: '500'},
                        color: '#475569',
                        maxRotation: 45,
                        minRotation: 45,
                        callback: function (value) {
                            let label = this.getLabelForValue(value);
                            return label.length > 18 ? label.slice(0, 18) + '...' : label;
                        }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {color: '#f1f5f9', drawBorder: false},
                    ticks: {
                        font: {size: 11},
                        color: '#64748b',
                        callback: (val) => val.toLocaleString('id-ID')
                    }
                }
            }
        }
    });

    const buildProdiChartData = (selectedFacultyName) => {
        const prodiInFaculty = rawProdi.filter(p => p.fakultas === selectedFacultyName);

        const cleanName = (name) => name.replace(/\s*\((D3|D4|S1|S2|S3|Profesi)\)/gi, '').trim();

        const prodiLabels = [...new Set(prodiInFaculty.map(p => cleanName(p.nama_prodi)))];

        const newDatasets = daftarJenjang.map(jenjang => {
            const labelJenjang = 'Mahasiswa ' + jenjang;
            const color = jenjangColors[labelJenjang] || {bg: '#3b82f6', text: '#ffffff'};

            const dataValues = prodiLabels.map(labelProdi => {
                return prodiInFaculty
                    .filter(p => cleanName(p.nama_prodi) === labelProdi && p.jenjang === jenjang)
                    .reduce((sum, p) => sum + parseInt(p.jumlah_mahasiswa_aktif || 0, 10), 0);
            });

            return {
                label: labelJenjang,
                data: dataValues,
                backgroundColor: color.bg,
                borderColor: '#ffffff',
                borderWidth: 1,
                borderRadius: 2,
                maxBarThickness: 48,
            };
        });

        return {labels: prodiLabels, datasets: newDatasets};
    };

    const facultySelect = root.querySelector('[data-statistik-mahasiswa-faculty]');
    const yearSelect = root.querySelector('[data-statistik-mahasiswa-year]');
    const semesterSelect = root.querySelector('[data-statistik-mahasiswa-semester]');

    facultySelect?.addEventListener('change', (e) => {
        const selectedFaculty = e.target.value;

        if (selectedFaculty === 'Semua Fakultas') {
            myChart.data.labels = [...originalLabels];
            myChart.data.datasets = styledDatasets.map(ds => ({
                ...ds,
                data: [...ds.data]
            }));
        } else {
            const prodiData = buildProdiChartData(selectedFaculty);
            myChart.data.labels = prodiData.labels;
            myChart.data.datasets = prodiData.datasets;
        }

        myChart.update();
    });

    const handleServerFilter = () => {
        const url = new URL(window.location.href);
        if (yearSelect) url.searchParams.set('tahun', yearSelect.value);
        if (semesterSelect) url.searchParams.set('semester', semesterSelect.value);
        window.location.href = url.toString();
    };

    yearSelect?.addEventListener('change', handleServerFilter);
    semesterSelect?.addEventListener('change', handleServerFilter);
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

            // 1. Tampilkan pesan user & aktifkan loading
            addMessage(message, 'user');
            input.value = '';
            autosizeInput();
            setLoading(true);

            // 2. Ambil konteks layar saat ini (jika user sedang di halaman statistik)
            const root = document.querySelector('[data-statistik-mahasiswa-root]');
            let currentContext = null;
            if (root) {
                const payload = JSON.parse(root.getAttribute('data-payload') || '{}');
                currentContext = {
                    halaman: "Statistik Mahasiswa Aktif",
                    tahun_terpilih: payload.defaultSelection?.year,
                    semester_terpilih: payload.defaultSelection?.semester,
                    fakultas_terpilih: payload.defaultSelection?.faculty
                };
            }

            try {
                // 3. Kirim pesan + konteks halaman ke endpoint Laravel
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
                        page_context: currentContext, // <-- Konteks terkirim dengan aman di sini
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    addMessage(payload.message ?? 'TirtaAgent belum bisa menjawab.', 'assistant');
                    return;
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
