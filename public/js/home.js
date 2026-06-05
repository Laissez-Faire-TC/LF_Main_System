
const hamburger = document.getElementById("hamburger");
const nav = document.getElementById("nav");
const activityToggle = document.getElementById("activityToggle");
const activityMenu = document.getElementById("activityMenu");

hamburger.addEventListener("click", () => {
  if (nav.style.display === "block") {
    nav.style.display = "none";
    hamburger.textContent = "☰";
  } else {
    nav.style.display = "block";
    hamburger.textContent = "×";
  }
});

if (activityToggle) {
    activityToggle.addEventListener("click", (e) => {
        e.stopPropagation();
        if (activityMenu.style.display === "block") {
            activityMenu.style.display = "none";
        } else {
            activityMenu.style.display = "block";
        }
    });
}

function openNewsModal(date, title, desc, imgSrc) {
    document.getElementById('modalDate').innerText = date;
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalDesc').innerText = desc;

    // 画像がない場合は非表示にする（空srcによる余分なリクエストを防ぐ）
    const img = document.getElementById('modalImg');
    if (imgSrc) {
        img.src = imgSrc;
        img.style.display = '';
    } else {
        img.src = '';
        img.style.display = 'none';
    }

    const modal = document.getElementById('newsModal');
    modal.style.display = 'block';
    setTimeout(() => { modal.classList.add('is-visible'); }, 10);
}

function closeNewsModal() {
    const modal = document.getElementById('newsModal');
    modal.classList.remove('is-visible');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

window.addEventListener("click", (event) => {
    const modal = document.getElementById('newsModal');
    if (event.target === modal) { closeNewsModal(); }
});

// ─────────────────────────────────────────
// HP コンテンツを API から取得
// ─────────────────────────────────────────
let scheduleData = {};

async function loadHpContent() {
    try {
        const res = await fetch('/api/hp/public');
        const data = await res.json();
        if (!data.success) return;

        const { settings, news, schedule } = data.data;

        // About セクション更新
        if (settings.about_description) {
            const aboutText = document.querySelector('.about-text');
            if (aboutText) aboutText.innerHTML = settings.about_description;
        }
        if (settings.about_info) {
            const info = typeof settings.about_info === 'string'
                ? JSON.parse(settings.about_info)
                : settings.about_info;
            const table = document.querySelector('.data-table');
            if (table && info.length > 0) {
                table.innerHTML = info.map(row =>
                    `<tr><th>${row.th}</th><td>${row.td}</td></tr>`
                ).join('');
            }
        }
        if (settings.about_achievements) {
            const achs = typeof settings.about_achievements === 'string'
                ? JSON.parse(settings.about_achievements)
                : settings.about_achievements;
            const achBox = document.querySelector('.achievements-box');
            if (achBox && achs.length > 0) {
                const title = achBox.querySelector('.ach-title');
                achBox.innerHTML = (title ? title.outerHTML : '<h4 class="ach-title">🏆 Recent Achievements</h4>') +
                    achs.map(a => `
                        <div class="ach-row">
                            <span class="ach-event">${a.event}</span>
                            <span class="ach-result">${a.result}</span>
                        </div>`).join('');
            }
        }

        // クイックニュース：最新2件を自動表示
        const quickList = document.querySelector('.quick-nav-inner ul');
        if (quickList && news && news.length > 0) {
            quickList.innerHTML = news.slice(0, 2).map(item =>
                `<li><a href="#${escHtml(item.anchor_id || 'news-' + item.id)}">${escHtml(item.news_date)} - ${escHtml(item.title)}</a></li>`
            ).join('');
        }

        // ニュースカード更新（空でも呼んで静的HTMLをクリア）
        renderNewsCards(news || []);

        // Contact リンク更新
        if (settings.contact_instagram) {
            const btn = document.querySelector('.sns-btn.insta');
            if (btn) btn.href = settings.contact_instagram;
        }
        if (settings.contact_twitter) {
            const btn = document.querySelector('.sns-btn.x-twitter');
            if (btn) btn.href = settings.contact_twitter;
        }

        // スケジュールデータを保存（openArticle で使用）
        scheduleData = schedule;

    } catch (e) {
        console.warn('HP content load failed, using static fallback.', e);
    }
}

function escHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderNewsCards(newsItems) {
    const grid = document.querySelector('.news-grid');
    if (!grid) return;

    // data属性にIDを持たせてonclick属性でのHTMLエンティティ二重デコード問題を回避
    grid.innerHTML = newsItems.map(item => `
        <div id="${escHtml(item.anchor_id || 'news-' + item.id)}" class="news-card"
             data-news-id="${escHtml(String(item.id))}">
            <div class="card-image">
                ${item.image_path ? `<img src="${escHtml(item.image_path)}" alt="${escHtml(item.title)}">` : ''}
            </div>
            <div class="card-body">
                <span class="date">${escHtml(item.news_date)}</span>
                <h3 class="card-title">${escHtml(item.title)}</h3>
                <p class="tap-hint">(タップで詳細表示)</p>
            </div>
        </div>`).join('');

    // ニュースデータをマップに保持しイベントリスナーで渡す（文字列をエンコードせず安全に渡せる）
    const newsMap = {};
    newsItems.forEach(item => { newsMap[String(item.id)] = item; });

    grid.querySelectorAll('.news-card[data-news-id]').forEach(card => {
        card.addEventListener('click', () => {
            const id = card.getAttribute('data-news-id');
            const item = newsMap[id];
            if (item) {
                openNewsModal(item.news_date || '', item.title || '', item.description || '', item.image_path || '');
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', loadHpContent);

// ─────────────────────────────────────────
// 記事表示（スケジュール）
// ─────────────────────────────────────────

let currentSlides = [];
let slideIdx = 0;
let slideshowResizeBound = false;

function syncSlideshowHeight() {
    const container = document.querySelector('.slideshow-container');
    if (!container || container.style.display === 'none') return;
    const img = container.querySelector('.slide-item.active img');
    if (!img) return;
    const nw = img.naturalWidth;
    const nh = img.naturalHeight;
    if (!nw || !nh) return;
    const w = container.clientWidth || window.innerWidth;
    let h = w * (nh / nw);
    const vh = window.innerHeight || 800;
    const minH = 220;
    const maxH = Math.min(900, Math.floor(vh * 0.85));
    h = Math.max(minH, Math.min(h, maxH));
    container.style.setProperty('--slideshow-height', `${Math.round(h)}px`);
}

function bindSlideshowResize() {
    if (slideshowResizeBound) return;
    slideshowResizeBound = true;
    window.addEventListener('resize', () => {
        const detailView = document.getElementById('detail-view');
        if (detailView && detailView.style.display === 'block') {
            syncSlideshowHeight();
        }
    });
}

function openArticle(key) {
    const data = scheduleData[key];
    if (!data) return;

    document.querySelectorAll('.schedule-card').forEach(card => {
        card.classList.remove('selected');
        if (card.getAttribute('data-schedule') === key) {
            card.classList.add('selected');
        }
    });

    // APIレスポンスは month_en / text_html / extra_html
    document.getElementById('article-month-tag').innerText = data.month_en || data.month || '';
    document.getElementById('article-title').innerText = data.title;
    document.getElementById('article-text').innerHTML = data.text_html || data.text || '';

    const specialCard = document.querySelector('.special-card');
    const extraHtml = data.extra_html || data.extra || '';
    if (key === 'april' && extraHtml) {
        specialCard.style.display = 'block';
        document.getElementById('extra-content').innerHTML = extraHtml;
    } else {
        specialCard.style.display = 'none';
    }

    const slideshowContainer = document.querySelector('.slideshow-container');
    const ctrlBtns = document.querySelectorAll('.slide-ctrl');
    const counter = document.getElementById('slide-counter');
    const wrapper = document.getElementById('slide-wrapper');

    const images = data.images || [];

    if (!images || images.length === 0) {
        slideshowContainer.style.display = 'none';
    } else {
        slideshowContainer.style.display = 'block';
        wrapper.innerHTML = '';
        images.forEach((src, i) => {
            const div = document.createElement('div');
            div.className = `slide-item ${i === 0 ? 'active' : ''}`;
            div.innerHTML = `<img src="${src}" alt="slide">`;
            wrapper.appendChild(div);
        });

        bindSlideshowResize();
        wrapper.querySelectorAll('img').forEach(img => {
            if (img.complete) { syncSlideshowHeight(); }
            else { img.addEventListener('load', syncSlideshowHeight, { once: true }); }
        });
        setTimeout(syncSlideshowHeight, 0);

        if (images.length === 1) {
            ctrlBtns.forEach(btn => btn.style.display = 'none');
            counter.style.display = 'none';
        } else {
            ctrlBtns.forEach(btn => btn.style.display = 'block');
            counter.style.display = 'block';
            currentSlides = images;
            slideIdx = 0;
            updateCounter();
        }
    }

    document.getElementById('main-view').style.display = 'none';
    document.getElementById('detail-view').style.display = 'block';
    window.scrollTo(0, 0);
}

function changeSlide(n) {
    const items = document.querySelectorAll('.slide-item');
    if (items.length <= 1) return;
    items[slideIdx].classList.remove('active');
    slideIdx = (slideIdx + n + items.length) % items.length;
    items[slideIdx].classList.add('active');
    updateCounter();
    syncSlideshowHeight();
}

function updateCounter() {
    document.getElementById('slide-counter').innerText = `${slideIdx + 1} / ${currentSlides.length}`;
}

function closeArticle() {
    const mainView = document.getElementById('main-view');
    const detailView = document.getElementById('detail-view');
    if (mainView && detailView) {
        mainView.style.display = 'block';
        detailView.style.display = 'none';
        document.body.style.overflow = '';
        const scheduleTarget = document.getElementById('schedule');
        if (scheduleTarget) {
            const targetPosition = scheduleTarget.getBoundingClientRect().top + window.pageYOffset;
            window.scrollTo({ top: targetPosition - 70, behavior: 'smooth' });
        }
    }
}
