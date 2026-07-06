import Chart from 'chart.js/auto';

const mobileMenuButton = document.querySelector('[data-mobile-menu-button]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

window.Chart = Chart;

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
        const typing = chat.querySelector('[data-tirta-typing]');
        const promptButtons = chat.querySelectorAll('[data-tirta-prompt]');
        const resetButtons = document.querySelectorAll('[data-tirta-chat-reset]');
        let conversationId = window.sessionStorage.getItem('tirta_agent_conversation_id');

        if (!form || !input || !messages || !submit || !status) {
            return;
        }

        const renderMarkdown = (content) => {
            if (window.marked && typeof window.marked.parse === 'function') {
                try {
                    return window.marked.parse(content);
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
            messages.scrollTop = messages.scrollHeight;
        };

        const autosizeInput = () => {
            input.style.height = 'auto';
            input.style.height = `${Math.min(input.scrollHeight, 128)}px`;
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
