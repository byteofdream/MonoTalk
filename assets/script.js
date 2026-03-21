/**
 * MonoTalk - Клиентские скрипты
 */

document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    initLoginForm();
    initRegisterForm();
    initCreatePostForm();
    initCreateSubredditForm();
    initCategoryQuickPick();
    initCommentForm();
    initLikeButtons();
    initProfileForm();
});

// Базовый URL из data-атрибута или текущий путь
const baseUrl = document.querySelector('base')?.href?.replace(/\/$/, '') || '';

function apiUrl(path) {
    const url = path.startsWith('/') ? path : baseUrl + '/' + path.replace(/^\//, '');
    return url.replace(/([^:])\/\//g, '$1/');
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
    window.location.href = 'index.php' + (params.toString() ? '?' + params : '');
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
                window.location.href = json.redirect || 'index.php';
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
                window.location.href = json.redirect || 'welcome.php';
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
                window.location.href = json.redirect || ('post.php?id=' + json.post_id);
            } else {
                alert(json.error || 'Ошибка создания поста');
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
                window.location.href = json.redirect || ('index.php?category=' + encodeURIComponent(json.subreddit.id));
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
                alert(json.error || 'Ошибка');
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
                window.location.reload();
            } else {
                alert(json.error || 'Ошибка сохранения');
            }
        } catch (err) {
            alert('Ошибка сети');
        }
    });
}
