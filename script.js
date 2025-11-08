// Fetch and render case stories into #storiesGrid
document.addEventListener('DOMContentLoaded', () => {
    initCaseStories();
});

async function initCaseStories() {
    const container = document.getElementById('storiesGrid');
    if (!container) return;
    container.innerHTML = '<div class="story-loading">載入中…</div>';

    // Try relative API first (for same-origin setups), then fallback to local backend host
    const candidates = [
        '/backend.php?action=list',
        '/backend/backend.php?action=list',
        'http://localhost:3307/backend.php?action=list'
    ];

    let data = null;
    for (const url of candidates) {
        try {
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            // support either { data: [...] } or raw array
            if (Array.isArray(json)) data = json;
            else if (Array.isArray(json.data)) data = json.data;
            else if (Array.isArray(json.stories)) data = json.stories;
            else {
                // try to detect a single object wrapper
                const arr = Object.values(json).find(v => Array.isArray(v));
                if (Array.isArray(arr)) data = arr;
            }
            if (data) break;
        } catch (err) {
            // try next candidate
            // console.debug('stories fetch failed', url, err);
            continue;
        }
    }

    if (!data) {
        container.innerHTML = '<div class="story-error">無法載入案例，請稍後再試。</div>';
        return;
    }

    renderCaseStories(container, data);
}

function renderCaseStories(container, stories) {
    if (!Array.isArray(stories) || stories.length === 0) {
        container.innerHTML = '<div class="story-empty">尚無成功案例。</div>';
        return;
    }

    // build grid
    container.innerHTML = '';
    stories.forEach(s => {
        const card = document.createElement('div');
        card.className = 'story-card';

        const header = document.createElement('div');
        header.className = 'story-header';

        const date = document.createElement('span');
        date.className = 'story-date';
        // accept multiple possible field names
        date.textContent = s.date || s.created_at || s.story_date || '';

        const lawyer = document.createElement('span');
        lawyer.className = 'story-lawyer';
        const lawyerName = s.lawyer || s.author || s.attorney || '';
        lawyer.textContent = lawyerName ? `承辦律師：${lawyerName}` : '';

        header.appendChild(date);
        header.appendChild(lawyer);

        const title = document.createElement('h3');
        title.className = 'story-title';
        title.textContent = s.title || s.story_title || '';

        const content = document.createElement('p');
        content.className = 'story-content';
        content.textContent = s.content || s.body || s.story_content || '';

        card.appendChild(header);
        card.appendChild(title);
        card.appendChild(content);

        container.appendChild(card);
    });
}

// Expose functions for debugging in dev console
window.fetchCaseStories = initCaseStories;
window.renderCaseStories = renderCaseStories;
