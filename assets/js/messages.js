const Messages = (function () {

    const API         = '/unihub/modules/messages.php';
    const PROFILE_API = '/unihub/modules/profile.php';
    let activeUserId   = null;
    let activeUserName = '';
    let refreshTimer   = null;
    let lastCount      = 0;

    /* ── Load ─────────────────────────────────────────── */

    async function load() {
        await loadConversations();
    }

    async function loadConversations() {
        const list = document.getElementById('conv-list');
        if (!list) return;
        list.innerHTML = '<div class="conv-loading">Loading…</div>';

        try {
            const res  = await fetch(API);
            const data = await res.json();
            renderConversations(data.conversations || []);
        } catch (e) {
            list.innerHTML = '<div class="conv-empty-msg">Unable to load conversations.</div>';
        }
    }

    function renderConversations(convs) {
        const list = document.getElementById('conv-list');
        list.innerHTML = '';

        if (convs.length === 0) {
            list.innerHTML =
                '<div class="conv-empty-msg">' +
                    '<span>\uD83D\uDCAC</span>' +
                    '<p>No conversations yet.</p>' +
                    '<small>Message a student from Skill Exchange or use <strong>New Chat</strong>.</small>' +
                '</div>';
            return;
        }

        convs.forEach(function (c) {
            var item = document.createElement('div');
            item.className = 'conv-item' + (c.other_id == activeUserId ? ' active' : '');
            item.dataset.userId = c.other_id;

            var initial = c.full_name ? c.full_name.charAt(0).toUpperCase() : '?';
            var avatarStyle = c.avatar
                ? 'style="background-image:url(' + escapeHtml(c.avatar) + ');background-size:cover;background-position:center;"'
                : '';

            item.innerHTML =
                '<div class="conv-avatar" ' + avatarStyle + '>' + (c.avatar ? '' : initial) + '</div>' +
                '<div class="conv-info">' +
                    '<div class="conv-name">' + escapeHtml(c.full_name) + '</div>' +
                    '<div class="conv-preview">' + escapeHtml(c.last_message) + '</div>' +
                '</div>';

            item.addEventListener('click', function () {
                openChat(c.other_id, c.full_name);
            });

            list.appendChild(item);
        });
    }

    /* ── Open Chat ────────────────────────────────────── */

    async function openChat(userId, userName) {
        activeUserId   = parseInt(userId);
        activeUserName = userName;
        lastCount      = 0;

        document.querySelectorAll('.conv-item').forEach(function (el) {
            el.classList.toggle('active', el.dataset.userId == userId);
        });

        var chatPanel = document.getElementById('chat-panel');
        var chatEmpty = document.getElementById('chat-empty');
        if (chatPanel) chatPanel.style.display = 'flex';
        if (chatEmpty) chatEmpty.style.display = 'none';

        var nameEl = document.getElementById('chat-header-name');
        if (nameEl) {
            nameEl.textContent = userName;
            nameEl.style.cursor = 'pointer';
            nameEl.onclick = function () {
                showProfilePopup(userId);
            };
        }

        var initial = userName ? userName.charAt(0).toUpperCase() : '?';
        var avatarEl = document.getElementById('chat-header-avatar');
        if (avatarEl) {
            avatarEl.textContent = initial;
            avatarEl.style.backgroundImage = '';
            avatarEl.style.cursor = 'pointer';
            avatarEl.onclick = function () {
                showProfilePopup(userId);
            };
        }

        await loadMessages();
        startAutoRefresh();

        var input = document.getElementById('chat-input');
        if (input) input.focus();
    }

    /* ── Load / Render Messages ───────────────────────── */

    async function loadMessages() {
        if (!activeUserId) return;

        try {
            var res  = await fetch(API + '?with=' + activeUserId);
            var data = await res.json();
            var msgs = data.messages || [];

            if (data.other_user) {
                var avatarEl = document.getElementById('chat-header-avatar');
                if (avatarEl) {
                    if (data.other_user.avatar) {
                        avatarEl.style.backgroundImage = 'url(' + escapeHtml(data.other_user.avatar) + ')';
                        avatarEl.style.backgroundSize = 'cover';
                        avatarEl.style.backgroundPosition = 'center';
                        avatarEl.textContent = '';
                    } else {
                        var initial = data.other_user.full_name ? data.other_user.full_name.charAt(0).toUpperCase() : '?';
                        avatarEl.style.backgroundImage = '';
                        avatarEl.textContent = initial;
                    }
                }
            }

            if (msgs.length !== lastCount) {
                lastCount = msgs.length;
                renderMessages(msgs);
            }
        } catch (e) {}
    }

    function renderMessages(messages) {
        var container   = document.getElementById('chat-messages');
        var currentUser = document.getElementById('current-user-id');
        if (!container || !currentUser) return;

        var myId = parseInt(currentUser.value);
        container.innerHTML = '';

        if (messages.length === 0) {
            container.innerHTML = '<div class="chat-no-msgs">No messages yet. Say hello!</div>';
            return;
        }

        messages.forEach(function (m) {
            var isMine = parseInt(m.sender_id) === myId;
            var row = document.createElement('div');
            row.className = 'chat-message-row ' + (isMine ? 'mine' : 'theirs');

            var avatarEl = document.createElement('div');
            avatarEl.className = 'chat-message-avatar';
            
            var initial = m.sender_name ? m.sender_name.charAt(0).toUpperCase() : '?';
            if (m.sender_avatar) {
                avatarEl.style.backgroundImage = 'url(' + escapeHtml(m.sender_avatar) + ')';
                avatarEl.style.backgroundSize = 'cover';
                avatarEl.style.backgroundPosition = 'center';
            } else {
                avatarEl.textContent = initial;
            }

            avatarEl.style.cursor = 'pointer';
            avatarEl.addEventListener('click', function () {
                showProfilePopup(m.sender_id);
            });

            var bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + (isMine ? 'mine' : 'theirs');
            bubble.innerHTML =
                '<div class="bubble-text">' + escapeHtml(m.message) + '</div>' +
                '<div class="bubble-time">' + formatTime(m.created_at) + '</div>';

            row.appendChild(avatarEl);
            row.appendChild(bubble);
            container.appendChild(row);
        });

        container.scrollTop = container.scrollHeight;
    }

    /* ── Send Message ─────────────────────────────────── */

    async function sendMessage() {
        if (!activeUserId) return;

        var input = document.getElementById('chat-input');
        var btn   = document.getElementById('chat-send-btn');
        if (!input || !btn) return;

        var text = input.value.trim();
        if (!text) return;

        btn.disabled = true;

        try {
            var res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ receiver_id: activeUserId, message: text })
            });
            var data = await res.json();

            if (res.ok) {
                input.value = '';
                lastCount = 0;
                await loadMessages();
                await loadConversations();
            }
        } catch (e) {
        } finally {
            btn.disabled = false;
            input.focus();
        }
    }

    /* ── Auto Refresh ─────────────────────────────────── */

    function startAutoRefresh() {
        stopAutoRefresh();
        refreshTimer = setInterval(loadMessages, 3000);
    }

    function stopAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }

    /* ── Open With User (from Skills) ─────────────────── */

    async function openWithUser(userId, userName) {
        if (typeof switchTab === 'function') switchTab('community');
        await loadConversations();
        await openChat(userId, userName);
    }

    /* ── New Chat / User Search ───────────────────────── */

    function openNewChatModal() {
        var existing = document.getElementById('new-chat-modal');
        if (existing) {
            existing.classList.add('show');
            document.getElementById('new-chat-search').value = '';
            document.getElementById('new-chat-results').innerHTML = '';
            document.getElementById('new-chat-search').focus();
            return;
        }

        var modal = document.createElement('div');
        modal.id = 'new-chat-modal';
        modal.className = 'new-chat-modal-overlay';
        modal.innerHTML = `
            <div class="new-chat-modal">
                <div class="new-chat-modal-header">
                    <h3>New Conversation</h3>
                    <button class="new-chat-close-btn" id="new-chat-close">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="new-chat-search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input type="text" id="new-chat-search" placeholder="Search students by name or email…" autocomplete="off">
                </div>
                <div id="new-chat-results" class="new-chat-results"></div>
            </div>
        `;
        document.body.appendChild(modal);

        setTimeout(function () { modal.classList.add('show'); }, 10);

        modal.querySelector('#new-chat-close').addEventListener('click', closeNewChatModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeNewChatModal();
        });

        var searchInput = modal.querySelector('#new-chat-search');
        var searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            if (q.length < 2) {
                document.getElementById('new-chat-results').innerHTML =
                    '<div class="new-chat-hint">Type at least 2 characters to search.</div>';
                return;
            }
            document.getElementById('new-chat-results').innerHTML =
                '<div class="new-chat-hint">Searching…</div>';
            searchTimer = setTimeout(function () { searchUsers(q); }, 300);
        });

        searchInput.focus();
    }

    function closeNewChatModal() {
        var modal = document.getElementById('new-chat-modal');
        if (modal) {
            modal.classList.remove('show');
        }
    }

    async function searchUsers(query) {
        var results = document.getElementById('new-chat-results');
        if (!results) return;

        try {
            var res  = await fetch(PROFILE_API + '?search=1&q=' + encodeURIComponent(query));
            var data = await res.json();
            var users = data.users || [];

            results.innerHTML = '';

            if (users.length === 0) {
                results.innerHTML = '<div class="new-chat-hint">No students found for "' + escapeHtml(query) + '".</div>';
                return;
            }

            users.forEach(function (u) {
                var item = document.createElement('div');
                item.className = 'new-chat-user-item';
                var initial = u.full_name ? u.full_name.charAt(0).toUpperCase() : '?';
                var avatarContent = u.avatar
                    ? '<div class="ncu-avatar" style="background-image:url(' + escapeHtml(u.avatar) + ');background-size:cover;background-position:center;"></div>'
                    : '<div class="ncu-avatar">' + initial + '</div>';
                item.innerHTML = avatarContent + '<span class="ncu-name">' + escapeHtml(u.full_name) + '</span>';

                item.addEventListener('click', function () {
                    closeNewChatModal();
                    openWithUser(u.id, u.full_name);
                });

                results.appendChild(item);
            });
        } catch (e) {
            results.innerHTML = '<div class="new-chat-hint">Error searching. Please try again.</div>';
        }
    }

    /* ── Helpers ──────────────────────────────────────── */

    function formatTime(str) {
        var d = new Date(str);
        return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ── Profile Popup ────────────────────────────────── */

    async function showProfilePopup(userId) {
        var modal = document.getElementById('user-profile-popup');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'user-profile-popup';
            modal.className = 'profile-popup-overlay';
            document.body.appendChild(modal);
        }

        modal.innerHTML =
            '<div class="profile-popup-card">' +
                '<button class="profile-popup-close" id="profile-popup-close-btn">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                '</button>' +
                '<div class="profile-popup-loading">Loading profile…</div>' +
            '</div>';
        
        modal.classList.add('show');

        modal.querySelector('#profile-popup-close-btn').addEventListener('click', closeProfilePopup);
        modal.onclick = function (e) {
            if (e.target === modal) closeProfilePopup();
        };

        try {
            var res = await fetch('/unihub/modules/profile.php?id=' + userId);
            if (!res.ok) throw new Error();
            var u = await res.json();

            var initial = u.full_name ? u.full_name.charAt(0).toUpperCase() : '?';
            var avatarHtml = u.avatar
                ? '<div class="popup-avatar" style="background-image:url(' + escapeHtml(u.avatar) + ');background-size:cover;background-position:center;"></div>'
                : '<div class="popup-avatar">' + initial + '</div>';

            var bioHtml = u.bio
                ? '<p class="popup-bio">"' + escapeHtml(u.bio) + '"</p>'
                : '<p class="popup-bio no-bio">No bio provided yet.</p>';

            var memberDate = new Date(u.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            var myId = parseInt(document.getElementById('current-user-id').value);
            var isMe = parseInt(u.id) === myId;
            var actionBtnHtml = !isMe
                ? '<button class="btn btn-primary btn-sm popup-msg-btn" id="popup-msg-btn">Send Message</button>'
                : '';

            modal.innerHTML =
                '<div class="profile-popup-card">' +
                    '<button class="profile-popup-close" id="profile-popup-close-btn">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                    '</button>' +
                    '<div class="profile-popup-body">' +
                        avatarHtml +
                        '<h3 class="popup-name">' + escapeHtml(u.full_name) + '</h3>' +
                        '<span class="popup-role">' + escapeHtml(u.role || 'Student') + '</span>' +
                        '<div class="popup-divider"></div>' +
                        bioHtml +
                        '<div class="popup-stats">' +
                            '<div class="popup-stat">' +
                                '<span class="ps-num">' + u.notes_count + '</span>' +
                                '<span class="ps-lbl">Notes</span>' +
                            '</div>' +
                            '<div class="popup-stat">' +
                                '<span class="ps-num">' + u.skills_count + '</span>' +
                                '<span class="ps-lbl">Skills</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="popup-footer">' +
                            '<small class="popup-joined">Joined ' + memberDate + '</small>' +
                            actionBtnHtml +
                        '</div>' +
                    '</div>' +
                '</div>';

            modal.querySelector('#profile-popup-close-btn').addEventListener('click', closeProfilePopup);
            
            var msgBtn = modal.querySelector('#popup-msg-btn');
            if (msgBtn) {
                msgBtn.onclick = function () {
                    closeProfilePopup();
                    openChat(u.id, u.full_name);
                };
            }
        } catch (e) {
            modal.innerHTML =
                '<div class="profile-popup-card">' +
                    '<button class="profile-popup-close" id="profile-popup-close-btn">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                    '</button>' +
                    '<div class="profile-popup-error">Unable to load profile.</div>' +
                '</div>';
            modal.querySelector('#profile-popup-close-btn').addEventListener('click', closeProfilePopup);
        }
    }

    function closeProfilePopup() {
        var modal = document.getElementById('user-profile-popup');
        if (modal) modal.classList.remove('show');
    }

    return { load, openWithUser, sendMessage, stopAutoRefresh, openNewChatModal, showProfilePopup };

})();

document.addEventListener('DOMContentLoaded', function () {
    var sendBtn  = document.getElementById('chat-send-btn');
    var input    = document.getElementById('chat-input');
    var newChatBtn = document.getElementById('new-chat-btn');

    if (sendBtn) sendBtn.addEventListener('click', Messages.sendMessage);

    if (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                Messages.sendMessage();
            }
        });
    }

    if (newChatBtn) {
        newChatBtn.addEventListener('click', Messages.openNewChatModal);
    }
});
