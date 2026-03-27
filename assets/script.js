/**
 * MonoTalk - Клиентские скрипты
 */

document.addEventListener('DOMContentLoaded', function() {
    window.LevelSystem?.mountProfileCard?.();
    initUserPresence();
    initFormattingToolbar();
    initFilters();
    initLoginForm();
    initRegisterForm();
    initCreatePostForm();
    initCreateSubredditForm();
    initCategoryQuickPick();
    initCommentForm();
    initLikeButtons();
    initProfileForm();
    initSubredditSearch();
    initSubscriptionButtons();
});

// Базовый URL из data-атрибута или текущий путь
const baseUrl = document.body?.dataset?.baseUrl || document.querySelector('base')?.href?.replace(/\/$/, '') || '';
const currentUserId = Number(document.body?.dataset?.currentUserId || 0);
const statusI18n = {
    online: document.body?.dataset?.statusOnline || '🟢 Online',
    recent: document.body?.dataset?.statusRecent || '⚫ Seen recently',
    minutes: document.body?.dataset?.statusMinutesTemplate || '⚫ Seen %d min ago',
    hours: document.body?.dataset?.statusHoursTemplate || '⚫ Seen %d hr ago',
    days: document.body?.dataset?.statusDaysTemplate || '⚫ Seen %d day(s) ago',
    weeks: document.body?.dataset?.statusWeeksTemplate || '⚫ Seen %d week(s) ago',
    months: document.body?.dataset?.statusMonthsTemplate || '⚫ Seen %d month(s) ago',
    years: document.body?.dataset?.statusYearsTemplate || '⚫ Seen %d year(s) ago'
};

function apiUrl(path) {
    const url = path.startsWith('/') ? path : baseUrl + '/' + path.replace(/^\//, '');
    return url.replace(/([^:])\/\//g, '$1/');
}

function resolveNavigationUrl(rawUrl, fallbackUrl) {
    const fallback = String(fallbackUrl || 'index.php').trim() || 'index.php';
    const candidate = String(rawUrl || '').trim();
    const target = candidate || fallback;

    if (!target || target === '#' || /^about:blank/i.test(target) || /^javascript:/i.test(target)) {
        return fallback;
    }

    try {
        const resolved = new URL(target, window.location.href);
        if (!/^https?:$/i.test(resolved.protocol)) {
            return fallback;
        }
        return resolved.href;
    } catch (error) {
        return fallback;
    }
}

function navigateTo(rawUrl, fallbackUrl) {
    window.location.assign(resolveNavigationUrl(rawUrl, fallbackUrl));
}

function initUserPresence() {
    if (!currentUserId) return;

    const throttleMs = 60000;
    let lastPingAt = 0;
    let pingInFlight = false;

    const ping = async (force = false) => {
        const now = Date.now();
        if (!force && document.visibilityState === 'hidden') return;
        if (!force && now - lastPingAt < throttleMs) return;
        if (pingInFlight) return;

        pingInFlight = true;
        try {
            const res = await fetch(apiUrl('api/activity_ping.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: currentUserId })
            });
            const json = await res.json();
            if (json.success && json.presence) {
                updateStatusNodes(currentUserId, json.presence);
                lastPingAt = now;
            }
        } catch (err) {
            console.error(err);
        } finally {
            pingInFlight = false;
        }
    };

    ping(true);
    ['click', 'keydown', 'scroll', 'touchstart'].forEach((eventName) => {
        window.addEventListener(eventName, () => ping(), { passive: true });
    });
    window.addEventListener('focus', () => ping());
    window.addEventListener('pageshow', () => ping());
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            ping();
        }
    });
    window.setInterval(() => ping(), throttleMs);
}

function formatPresenceLabel(presence) {
    if (!presence || !presence.last_seen) {
        return statusI18n.recent;
    }

    if (presence.is_online || presence.status === 'online') {
        return statusI18n.online;
    }

    const lastSeenTs = Number(presence.last_seen_timestamp || 0) * 1000;
    const elapsedMs = lastSeenTs > 0 ? Math.max(0, Date.now() - lastSeenTs) : 0;

    if (elapsedMs < 60000) {
        return statusI18n.recent;
    }

    const periods = [
        { ms: 31536000000, template: statusI18n.years },
        { ms: 2592000000, template: statusI18n.months },
        { ms: 604800000, template: statusI18n.weeks },
        { ms: 86400000, template: statusI18n.days },
        { ms: 3600000, template: statusI18n.hours },
        { ms: 60000, template: statusI18n.minutes }
    ];

    for (const period of periods) {
        if (elapsedMs >= period.ms) {
            const value = Math.max(1, Math.floor(elapsedMs / period.ms));
            return period.template.replace('%d', String(value));
        }
    }

    return statusI18n.recent;
}

function updateStatusNodes(userId, presence) {
    document.querySelectorAll(`[data-user-status][data-user-id="${userId}"]`).forEach((node) => {
        node.textContent = formatPresenceLabel(presence);
        node.dataset.status = presence.status || 'offline';
        node.dataset.lastSeen = presence.last_seen || '';
        node.classList.toggle('is-online', Boolean(presence.is_online || presence.status === 'online'));
        node.classList.toggle('is-offline', !Boolean(presence.is_online || presence.status === 'online'));
    });
}

const nativeAlert = window.alert.bind(window);

function ensureAppPopup() {
    let popup = document.getElementById('appPopup');
    if (popup) return popup;

    popup = document.createElement('div');
    popup.id = 'appPopup';
    popup.className = 'app-popup-overlay';
    popup.innerHTML = `
        <div class="app-popup-card" role="dialog" aria-modal="true" aria-labelledby="appPopupTitle">
            <button type="button" class="app-popup-close" aria-label="Close">&times;</button>
            <div class="app-popup-badge" id="appPopupBadge">Notice</div>
            <h3 class="app-popup-title" id="appPopupTitle">Forum message</h3>
            <p class="app-popup-text" id="appPopupText"></p>
            <button type="button" class="btn-primary app-popup-action">OK</button>
        </div>
    `;

    document.body.appendChild(popup);

    const close = () => popup.classList.remove('is-visible');
    popup.addEventListener('click', (event) => {
        if (event.target === popup) close();
    });
    popup.querySelector('.app-popup-close').addEventListener('click', close);
    popup.querySelector('.app-popup-action').addEventListener('click', close);
    return popup;
}

function inferPopupSeverity(message) {
    const text = String(message || '').toLowerCase();
    if (text.includes('ban') || text.includes('blocked') || text.includes('strike')) return 'high';
    if (text.includes('mute') || text.includes('error') || text.includes('ошиб')) return 'medium';
    return 'low';
}

function showAppPopup(message, options = {}) {
    const popup = ensureAppPopup();
    const severity = options.severity || inferPopupSeverity(message);

    popup.dataset.severity = severity;
    popup.querySelector('#appPopupTitle').textContent = options.title || 'Forum message';
    popup.querySelector('#appPopupBadge').textContent = severity.toUpperCase();
    popup.querySelector('#appPopupText').textContent = String(message || 'Something happened');
    popup.classList.add('is-visible');
}

window.alert = function(message) {
    try {
        showAppPopup(message);
    } catch (error) {
        nativeAlert(message);
    }
};

function getModerationMessage(json, fallback) {
    if (!json || !json.moderation) {
        return json?.error || fallback;
    }

    const parts = [json.moderation.reason || json.error || fallback];
    if (json.moderation.strike_added) {
        parts.push('Strikes: ' + (json.moderation.strikes || 0));
    }
    if (json.moderation.status === 'muted' && json.moderation.mute_until) {
        parts.push('Muted until: ' + json.moderation.mute_until);
    }
    if (json.moderation.status === 'banned') {
        parts.push('Account is banned');
    }

    return parts.join('\n');
}

// Фильтры на главной
function initFilters() {
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', applyFilters);
    }
    if (sortFilter) {
        sortFilter.addEventListener('change', applyFilters);
    }
}

function applyFilters() {
    const category = document.getElementById('categoryFilter')?.value || '';
    const sort = document.getElementById('sortFilter')?.value || 'new';
    const params = new URLSearchParams();
    if (category) params.set('category', category);
    if (sort !== 'new') params.set('sort', sort);
    navigateTo('index.php' + (params.toString() ? '?' + params : ''), 'index.php');
}

function initFormattingToolbar() {
    document.querySelectorAll('.format-btn').forEach((button) => {
        if (button.dataset.initialized) return;
        button.dataset.initialized = '1';

        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-format-target');
            const wrap = button.getAttribute('data-format-wrap') || '';
            const textarea = targetId ? document.getElementById(targetId) : null;
            if (!textarea) return;

            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? textarea.value.length;
            const selected = textarea.value.slice(start, end) || (wrap === '**' ? 'bold text' : 'italic text');
            const replacement = `${wrap}${selected}${wrap}`;

            textarea.setRangeText(replacement, start, end, 'end');
            textarea.focus();
        });
    });
}

// Форма входа
function initLoginForm() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Вход...';
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        try {
            const res = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            
            if (json.success) {
                navigateTo(json.redirect, 'index.php');
            } else {
                alert(json.error || 'Ошибка входа');
                btn.disabled = false;
                btn.textContent = 'Войти';
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = 'Войти';
        }
    });
}

// Форма регистрации
function initRegisterForm() {
    const form = document.getElementById('registerForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Регистрация...';
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        try {
            const res = await fetch('api/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            
            if (json.success) {
                navigateTo(json.redirect, 'welcome.php');
            } else {
                alert(json.error || 'Ошибка регистрации');
                btn.disabled = false;
                btn.textContent = 'Зарегистрироваться';
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = 'Зарегистрироваться';
        }
    });
}

// Форма создания поста
function initCreatePostForm() {
    const form = document.getElementById('createPostForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        const title = form.querySelector('#title').value.trim();
        const content = form.querySelector('#content').value.trim();
        const imageFile = form.querySelector('[name="image"]')?.files?.[0] || null;
        
        if (!title) {
            alert('Заполните заголовок');
            return;
        }

        if (!content && !imageFile) {
            alert('Добавьте текст или изображение');
            return;
        }
        
        btn.disabled = true;
        btn.textContent = 'Публикация...';
        
        const formData = new FormData();
        formData.append('title', title);
        formData.append('content', content);
        formData.append('category', form.querySelector('#category').value);
        formData.append('anonymous', form.querySelector('[name="anonymous"]').checked ? '1' : '0');
        if (imageFile) formData.append('image', imageFile);
        
        try {
            const res = await fetch('api/create_post.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            
            if (json.success) {
                window.LevelSystem?.applyLevelingUpdate?.(json.leveling);
                const fallbackPostId = Number(json.post_id);
                const fallbackPostUrl = Number.isFinite(fallbackPostId) && fallbackPostId > 0
                    ? ('post.php?id=' + fallbackPostId)
                    : 'index.php';
                navigateTo(json.redirect, fallbackPostUrl);
            } else {
                alert(getModerationMessage(json, 'Ошибка создания поста'));
                btn.disabled = false;
                btn.textContent = 'Опубликовать';
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = 'Опубликовать';
        }
    });
}

// Форма создания сабреддита
function initCreateSubredditForm() {
    const form = document.getElementById('createSubredditForm');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = form.querySelector('button[type=\"submit\"]');
        const name = form.querySelector('#subreddit_name').value.trim();
        const description = form.querySelector('#subreddit_desc').value.trim();
        const emoji = form.querySelector('#subreddit_emoji').value.trim();

        if (name.length < 3) {
            alert('Название слишком короткое');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Создание...';

        const formData = new FormData();
        formData.append('name', name);
        formData.append('description', description);
        formData.append('emoji', emoji);

        try {
            const res = await fetch('api/create_subreddit.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.success) {
                const fallbackCategoryId = json?.subreddit?.id ? encodeURIComponent(String(json.subreddit.id)) : '';
                const fallbackCategoryUrl = fallbackCategoryId ? ('index.php?category=' + fallbackCategoryId) : 'index.php';
                navigateTo(json.redirect, fallbackCategoryUrl);
            } else {
                alert(json.error || 'Ошибка создания сабреддита');
                btn.disabled = false;
                btn.textContent = 'Создать';
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = 'Создать';
        }
    });
}

// Быстрый выбор категории на create.php
function initCategoryQuickPick() {
    const select = document.getElementById('category');
    const quickButtons = document.querySelectorAll('.category-quick-btn');
    if (!select || !quickButtons.length) return;

    const syncActive = () => {
        quickButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.category === select.value);
        });
    };

    quickButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            select.value = btn.dataset.category || '';
            syncActive();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    select.addEventListener('change', syncActive);
    syncActive();
}

// Форма комментария
function initCommentForm() {
    const form = document.getElementById('commentForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const textarea = form.querySelector('textarea');
        const content = textarea.value.trim();
        const imageFile = form.querySelector('[name="image"]')?.files?.[0] || null;
        
        if (!content && !imageFile) {
            alert('Комментарий не может быть пустым');
            return;
        }
        
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('post_id', form.querySelector('[name="post_id"]').value);
        formData.append('content', content);
        formData.append('anonymous', form.querySelector('[name="anonymous"]').checked ? '1' : '0');
        if (imageFile) formData.append('image', imageFile);
        
        try {
            const res = await fetch('api/add_comment.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            
            if (json.success) {
                window.LevelSystem?.applyLevelingUpdate?.(json.leveling);
                const commentsList = document.querySelector('.comments-list');
                const comment = document.createElement('div');
                comment.className = 'comment-card-reddit';
                comment.dataset.id = json.comment.id;
                const verifiedHtml = json.comment.verified ? '<span class="verified-badge" title="Verified" aria-label="Verified">✔</span>' : '';
                const imageHtml = json.comment.image ? `<div class="comment-image-wrap"><img src="${escapeHtml(json.comment.image)}" class="comment-image" alt=""></div>` : '';
                comment.innerHTML = `
                    <div class="comment-vote-side">
                        <button class="vote-btn like-btn like-btn-sm" data-type="comment" data-id="${json.comment.id}">
                            <span class="vote-icon">▲</span>
                        </button>
                        <span class="vote-count">0</span>
                    </div>
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-author">u/${escapeHtml(json.comment.author_name)}${verifiedHtml}</span>
                            <span class="comment-date">только что</span>
                        </div>
                        <p class="comment-content">${escapeHtml(json.comment.content)}</p>
                        ${imageHtml}
                    </div>
                `;
                commentsList.appendChild(comment);
                initLikeButtons();
                textarea.value = '';
                form.querySelector('[name="anonymous"]').checked = false;
                const imageInput = form.querySelector('[name="image"]');
                if (imageInput) imageInput.value = '';
                
                const h2 = document.querySelector('#comments h2');
                if (h2) {
                    const count = commentsList.querySelectorAll('.comment-card-reddit').length;
                    h2.textContent = 'Комментарии (' + count + ')';
                }
            } else {
                alert(getModerationMessage(json, 'Ошибка'));
            }
        } catch (err) {
            alert('Ошибка сети');
        }
        btn.disabled = false;
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Кнопки лайков
function initLikeButtons() {
    document.querySelectorAll('.like-btn').forEach(btn => {
        if (btn.dataset.initialized) return;
        btn.dataset.initialized = '1';
        btn.addEventListener('click', handleLike);
    });
}

async function handleLike(e) {
    const btn = e.currentTarget;
    if (btn.disabled) return;
    
    const type = btn.dataset.type;
    const id = btn.dataset.id;
    const countEl = btn.querySelector('.like-count') || btn.parentElement?.querySelector('.vote-count');
    const wasLiked = btn.classList.contains('liked');
    
    try {
        const res = await fetch('api/like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, target_id: id })
        });
        const json = await res.json();
        
        if (json.success) {
            if (countEl) countEl.textContent = json.count;
            btn.classList.toggle('liked', json.liked);
        }
    } catch (err) {
        console.error(err);
    }
}

// Профиль
function initProfileForm() {
    const form = document.getElementById('profileForm');
    if (!form) return;

    const bio = form.querySelector('#profileBio');
    const bioCounter = document.getElementById('profileBioCounter');
    const preview = document.getElementById('profilePreview');
    const syncBioCounter = () => {
        if (!bio || !bioCounter) return;
        const template = bioCounter.dataset.template || '%d / 300';
        bioCounter.textContent = template.replace('%d', String((bio.value || '').length));
    };

    const escapePreview = (value) => escapeHtml(String(value || ''));
    const buildExternalLink = (rawValue, label) => {
        const trimmed = String(rawValue || '').trim();
        if (!trimmed) return '';
        const safeHref = /^https?:\/\//i.test(trimmed) ? trimmed : '';
        if (!safeHref) return '';
        return `<a href="${escapePreview(safeHref)}" target="_blank" rel="noopener noreferrer nofollow" class="profile-link-chip">${escapePreview(label)}</a>`;
    };

    const buildTelegramHref = (rawValue) => {
        const trimmed = String(rawValue || '').trim();
        if (!trimmed) return '';
        if (/^https?:\/\//i.test(trimmed)) return trimmed;
        const username = trimmed.replace(/^@/, '');
        return /^[A-Za-z0-9_]{3,32}$/.test(username) ? `https://t.me/${username}` : '';
    };

    const buildDiscordHref = (rawValue) => {
        const trimmed = String(rawValue || '').trim();
        if (!trimmed) return '';
        if (/^https?:\/\//i.test(trimmed)) return trimmed;
        return `https://discord.com/users/${encodeURIComponent(trimmed)}`;
    };

    const syncPreview = () => {
        if (!preview) return;

        const bioValue = String(form.querySelector('[name="bio"]')?.value || '').trim();
        const otherLinks = String(form.querySelector('[name="other_links"]')?.value || '')
            .split(/\r?\n/)
            .map((item) => item.trim())
            .filter((item) => /^https?:\/\//i.test(item));

        const linksHtml = [
            buildExternalLink(form.querySelector('[name="website"]')?.value, preview.dataset.websiteLabel || 'Website'),
            buildExternalLink(form.querySelector('[name="github"]')?.value, preview.dataset.githubLabel || 'GitHub'),
            buildExternalLink(buildTelegramHref(form.querySelector('[name="telegram"]')?.value), preview.dataset.telegramLabel || 'Telegram'),
            buildExternalLink(buildDiscordHref(form.querySelector('[name="discord"]')?.value), preview.dataset.discordLabel || 'Discord'),
            ...otherLinks.map((href, index) => buildExternalLink(href, `${preview.dataset.otherLabel || 'Link'} ${index + 1}`))
        ].filter(Boolean);

        if (!bioValue && linksHtml.length === 0) {
            preview.innerHTML = `<p class="profile-preview-empty">${escapePreview(preview.dataset.emptyText || 'Nothing to preview yet.')}</p>`;
            return;
        }

        preview.innerHTML = `
            ${bioValue ? `<p class="profile-preview-bio">${escapePreview(bioValue).replace(/\n/g, '<br>')}</p>` : ''}
            ${linksHtml.length ? `<div class="profile-links-list">${linksHtml.join('')}</div>` : ''}
        `;
    };

    if (bio) {
        bio.addEventListener('input', syncBioCounter);
    }

    form.querySelectorAll('input, textarea').forEach((field) => {
        field.addEventListener('input', syncPreview);
    });

    syncBioCounter();
    syncPreview();
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        
        try {
            const res = await fetch('api/update_profile.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            
            if (json.success) {
                navigateTo(json.redirect, 'profile.php');
            } else {
                alert(json.error || 'Ошибка сохранения');
            }
        } catch (err) {
            alert('Ошибка сети');
        }
    });
}

// Поиск сабреддитов в боковой панели
function initSubredditSearch() {
    const searchInput = document.getElementById('subredditSearch');
    const categoryList = document.getElementById('categoryList');
    
    if (!searchInput || !categoryList) return;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const items = categoryList.querySelectorAll('.category-item');
        
        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            if (query === '' || name.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// Подписка/отписка на сабреддиты
function initSubscriptionButtons() {
    const subscribeButtons = document.querySelectorAll('.subscribe-btn');
    
    subscribeButtons.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const subredditId = this.getAttribute('data-subreddit-id');
            let action = this.getAttribute('data-action');
            const isSubscribing = action === 'subscribe';
            
            if (!subredditId) return;
            
            try {
                const res = await fetch(apiUrl('api/toggle_subscription.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subreddit_id: subredditId,
                        action: action
                    })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    if (isSubscribing) {
                        // Switch to unsubscribe
                        this.textContent = this.getAttribute('data-unsubscribe-text') || 'Отписаться';
                        this.setAttribute('data-action', 'unsubscribe');
                    } else {
                        // Switch to subscribe
                        this.textContent = this.getAttribute('data-subscribe-text') || 'Подписаться';
                        this.setAttribute('data-action', 'subscribe');
                    }
                    const subscribersCount = document.getElementById('subscribersCount');
                    if (subscribersCount && typeof data.subscribers_count !== 'undefined') {
                        subscribersCount.textContent = data.subscribers_count;
                    }
                } else {
                    alert(data.error || 'Ошибка');
                }
            } catch (err) {
                alert('Ошибка сети');
            }
        });
    });
}
