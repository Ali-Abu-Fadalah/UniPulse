const Skills = (function () {

    const API = '/unihub/modules/skills.php';
    let editId = null;

    async function load(query = '') {
        setLoading(true, query);
        try {
            const url  = query ? API + '?q=' + encodeURIComponent(query) : API;
            const res  = await fetch(url);
            const data = await res.json();

            if (query === '') {
                renderMySkills(data.my_skills || []);
                renderMatches(data.matches   || []);
            }
            renderOthers(data.others     || []);
        } catch (e) {
            document.getElementById('skill-error').textContent = 'Unable to load skills. Please try again.';
        } finally {
            setLoading(false);
        }
    }

    function renderMySkills(skills) {
        const list  = document.getElementById('my-skills-list');
        const count = document.getElementById('my-skills-count');

        count.textContent = skills.length + ' / 10';
        list.innerHTML = '';

        if (skills.length === 0) {
            list.innerHTML = '<p class="skill-empty-msg">No skills added yet. Add your first one.</p>';
            return;
        }

        skills.forEach(function (s) {
            const el = document.createElement('div');
            el.className = 'my-skill-row';
            el.dataset.id = s.id;
            el.innerHTML =
                '<div class="skill-pair">' +
                    '<span class="skill-tag offer">' + escapeHtml(s.offered) + '</span>' +
                    '<span class="skill-arrow">⇄</span>' +
                    '<span class="skill-tag need">' + escapeHtml(s.needed) + '</span>' +
                '</div>' +
                '<div style="display:flex;gap:0.25rem;">' +
                    '<button class="skill-edit-btn" data-id="' + s.id + '" title="Edit">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>' +
                    '</button>' +
                    '<button class="skill-del-btn" data-id="' + s.id + '" title="Remove">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>' +
                    '</button>' +
                '</div>';

            el.querySelector('.skill-del-btn').addEventListener('click', function () {
                deleteSkill(s.id, el);
            });

            el.querySelector('.skill-edit-btn').addEventListener('click', function () {
                editId = s.id;
                document.getElementById('skill-offered').value = s.offered;
                document.getElementById('skill-needed').value = s.needed;
                document.getElementById('skill-add-btn').textContent = 'Save Changes';
                document.getElementById('skill-offered').focus();
            });

            list.appendChild(el);
        });
    }

    function renderMatches(matches) {
        const section = document.getElementById('matches-section');
        const list    = document.getElementById('matches-list');
        const count   = document.getElementById('matches-count');

        if (matches.length === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        count.textContent = matches.length + (matches.length === 1 ? ' match found' : ' matches found');
        list.innerHTML = '';

        matches.forEach(function (m) {
            const card = document.createElement('div');
            card.className = 'match-card';

            const badgeText  = m.match_type === 'both'       ? '🔥 Perfect Match' :
                               m.match_type === 'they_offer' ? '✅ They can teach you' :
                               '⭐ They want to learn from you';
            const badgeClass = m.match_type === 'both' ? 'badge-perfect' :
                               m.match_type === 'they_offer' ? 'badge-offer' : 'badge-need';

            card.innerHTML =
                '<div class="match-header">' +
                    '<div class="match-avatar">' + escapeHtml(m.full_name.charAt(0).toUpperCase()) + '</div>' +
                    '<div class="match-info">' +
                        '<div class="match-name">' + escapeHtml(m.full_name) + '</div>' +
                        '<span class="match-badge ' + badgeClass + '">' + badgeText + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="match-skills">' +
                    '<div class="match-skill-row">' +
                        '<span class="ms-label">Offers:</span>' +
                        '<span class="skill-tag offer sm">' + escapeHtml(m.offered) + '</span>' +
                    '</div>' +
                    '<div class="match-skill-row">' +
                        '<span class="ms-label">Needs:</span>' +
                        '<span class="skill-tag need sm">' + escapeHtml(m.needed) + '</span>' +
                    '</div>' +
                '</div>' +
                '<button class="match-msg-btn" data-uid="' + m.match_user_id + '" data-name="' + escapeHtml(m.full_name) + '">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>' +
                    'Message' +
                '</button>';

            card.querySelector('.match-msg-btn').addEventListener('click', function () {
                var uid  = parseInt(this.dataset.uid);
                var name = this.dataset.name;
                if (typeof Messages !== 'undefined') Messages.openWithUser(uid, name);
            });

            list.appendChild(card);
        });
    }

    function renderOthers(others) {
        const list  = document.getElementById('others-list');
        const count = document.getElementById('others-count');

        count.textContent = others.length === 0 ? '' : others.length + ' listings';
        list.innerHTML = '';

        if (others.length === 0) {
            list.innerHTML = '<p class="skill-empty-msg">No other students have added skills yet. Be the first.</p>';
            return;
        }

        others.forEach(function (s) {
            const card = document.createElement('div');
            card.className = 'other-skill-card';
            card.innerHTML =
                '<div class="other-avatar">' + escapeHtml(s.full_name.charAt(0).toUpperCase()) + '</div>' +
                '<div class="other-body">' +
                    '<div class="other-name">' + escapeHtml(s.full_name) + '</div>' +
                    '<div class="other-pairs">' +
                        '<span class="skill-tag offer sm">' + escapeHtml(s.offered) + '</span>' +
                        '<span class="skill-arrow sm">⇄</span>' +
                        '<span class="skill-tag need sm">' + escapeHtml(s.needed) + '</span>' +
                    '</div>' +
                '</div>' +
                '<button class="other-msg-btn" data-uid="' + s.match_user_id + '" data-name="' + escapeHtml(s.full_name) + '" title="Message ' + escapeHtml(s.full_name) + '">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>' +
                '</button>';

            card.querySelector('.other-msg-btn').addEventListener('click', function () {
                var uid  = parseInt(this.dataset.uid);
                var name = this.dataset.name;
                if (typeof Messages !== 'undefined') Messages.openWithUser(uid, name);
            });

            list.appendChild(card);
        });
    }

    async function add() {
        const offeredInput = document.getElementById('skill-offered');
        const neededInput  = document.getElementById('skill-needed');
        const btn          = document.getElementById('skill-add-btn');
        const error        = document.getElementById('skill-error');

        const offered = offeredInput.value.trim();
        const needed  = neededInput.value.trim();

        error.textContent = '';

        if (!offered || !needed) {
            error.textContent = 'Fill in both fields to continue.';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Adding…';

        try {
            const res  = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(editId ? { action: 'edit', id: editId, offered, needed } : { action: 'add', offered, needed })
            });
            const data = await res.json();

            if (!res.ok) {
                error.textContent = data.error || 'Something went wrong.';
                return;
            }

            offeredInput.value = '';
            neededInput.value  = '';
            editId = null;

            await load();

        } catch (e) {
            error.textContent = 'Connection error. Please try again.';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Add Skill';
        }
    }

    async function deleteSkill(id, el) {
        el.style.opacity = '0.4';
        el.style.pointerEvents = 'none';

        try {
            const res  = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const data = await res.json();

            if (!res.ok) {
                el.style.opacity = '1';
                el.style.pointerEvents = 'auto';
                alert(data.error || 'Could not remove skill.');
                return;
            }

            await load();

        } catch (e) {
            el.style.opacity = '1';
            el.style.pointerEvents = 'auto';
            alert('Network error. Try again.');
        }
    }

    function setLoading(on, query = '') {
        const btn = document.getElementById('skill-add-btn');
        if (btn) btn.disabled = on;

        if (on) {
            const skeletonHTML = 
                '<div class="skill-card skeleton" style="height: 60px;"></div>' +
                '<div class="skill-card skeleton" style="height: 60px;"></div>';
            const gridSkeletonHTML = 
                '<div class="match-card skeleton" style="height: 150px;"></div>' +
                '<div class="match-card skeleton" style="height: 150px;"></div>' +
                '<div class="match-card skeleton" style="height: 150px;"></div>';

            if (query === '') {
                const mySkillsList = document.getElementById('my-skills-list');
                const matchesList = document.getElementById('matches-list');
                if (mySkillsList) mySkillsList.innerHTML = skeletonHTML;
                if (matchesList) matchesList.innerHTML = gridSkeletonHTML;
            }
            const othersList = document.getElementById('others-list');
            if (othersList) othersList.innerHTML = gridSkeletonHTML;
        }

        const spinner = document.getElementById('skills-loading');
        if (spinner) spinner.style.display = on ? 'block' : 'none';
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return { load, add };

})();

document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('skill-add-btn');
    if (btn) btn.addEventListener('click', Skills.add);

    const searchInput = document.getElementById('skill-search');
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                Skills.load(searchInput.value.trim());
            }, 300);
        });
    }
});
