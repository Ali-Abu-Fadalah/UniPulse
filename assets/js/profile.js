const Profile = (function () {
    'use strict';
    const API = 'modules/profile.php';

    async function load() {
        try {
            const res  = await fetch(API);
            const data = await res.json();
            render(data);
        } catch (e) {
            document.getElementById('profile-save-msg').textContent = 'Unable to load profile.';
        }
    }

    function render(data) {
        setAvatar(data.avatar, data.full_name);

        document.getElementById('profile-display-name').textContent  = data.full_name;
        document.getElementById('profile-display-email').textContent = data.email;
        document.getElementById('profile-display-bio').textContent   = data.bio || 'No bio added yet.';

        document.getElementById('stat-notes').textContent  = data.notes_count;
        document.getElementById('stat-skills').textContent = data.skills_count;
        document.getElementById('stat-since').textContent  = formatDate(data.created_at);

        document.getElementById('profile-name-input').value = data.full_name;
        document.getElementById('profile-bio-input').value  = data.bio || '';
        updateBioCount();
    }

    function setAvatar(avatarUrl, name) {
        var els = document.querySelectorAll('.profile-avatar-circle, .user-avatar, #chat-header-avatar');
        els.forEach(function (el) {
            if (avatarUrl) {
                el.style.backgroundImage  = 'url(' + avatarUrl + ')';
                el.style.backgroundSize   = 'cover';
                el.style.backgroundPosition = 'center';
                el.textContent = '';
            } else {
                el.style.backgroundImage = '';
                el.textContent = name ? name.charAt(0).toUpperCase() : '?';
            }
        });
    }

    function formatDate(str) {
        if (!str) return '—';
        var d = new Date(str);
        return d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    }

    function updateBioCount() {
        var ta    = document.getElementById('profile-bio-input');
        var count = document.getElementById('bio-char-count');
        if (!ta || !count) return;
        count.textContent = ta.value.length + ' / 300';
        count.style.color = ta.value.length > 270 ? 'var(--error)' : 'var(--text-muted)';
    }

    function previewAvatar(file) {
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            document.querySelectorAll('.profile-avatar-circle').forEach(function (el) {
                el.style.backgroundImage   = 'url(' + e.target.result + ')';
                el.style.backgroundSize    = 'cover';
                el.style.backgroundPosition = 'center';
                el.textContent = '';
            });
            document.getElementById('avatar-filename').textContent = file.name;
        };
        reader.readAsDataURL(file);
    }

    async function save() {
        var btn     = document.getElementById('profile-save-btn');
        var msg     = document.getElementById('profile-save-msg');
        var name    = document.getElementById('profile-name-input').value.trim();
        var bio     = document.getElementById('profile-bio-input').value.trim();
        var fileEl  = document.getElementById('avatar-input');

        msg.textContent = '';
        msg.className   = 'profile-save-msg';

        if (!name) {
            msg.textContent = 'Name cannot be empty.';
            msg.classList.add('msg-error');
            return;
        }

        var form = new FormData();
        form.append('full_name', name);
        form.append('bio', bio);

        if (fileEl.files.length > 0) {
            form.append('avatar', fileEl.files[0]);
        }

        btn.disabled     = true;
        btn.textContent  = 'Saving…';

        try {
            var res  = await fetch(API, { method: 'POST', body: form });
            var data = await res.json();

            if (!res.ok) {
                msg.textContent = data.error || 'Something went wrong.';
                msg.classList.add('msg-error');
                return;
            }

            document.getElementById('profile-display-name').textContent = data.full_name;
            document.getElementById('profile-display-bio').textContent  = data.bio || 'No bio added yet.';

            var sidebarName = document.querySelector('.user-info .name');
            if (sidebarName) sidebarName.textContent = data.full_name;

            // Update topbar avatar immediately
            var topbarAvatar = document.querySelector('.topbar .user-avatar');
            if (topbarAvatar) {
                if (data.avatar) {
                    topbarAvatar.style.backgroundImage    = 'url(' + data.avatar + ')';
                    topbarAvatar.style.backgroundSize     = 'cover';
                    topbarAvatar.style.backgroundPosition = 'center';
                    topbarAvatar.textContent = '';
                } else {
                    topbarAvatar.style.backgroundImage = '';
                    topbarAvatar.textContent = data.full_name ? data.full_name.charAt(0).toUpperCase() : '?';
                }
            }

            msg.textContent = '✓ Profile updated successfully.';
            msg.classList.add('msg-success');

            fileEl.value = '';
            document.getElementById('avatar-filename').textContent = '';

        } catch (e) {
            msg.textContent = 'Connection error. Please try again.';
            msg.classList.add('msg-error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Save Changes';
        }
    }

    return { load, save, updateBioCount, previewAvatar };

})();

document.addEventListener('DOMContentLoaded', function () {
    var saveBtn  = document.getElementById('profile-save-btn');
    var bioInput = document.getElementById('profile-bio-input');
    var fileInput = document.getElementById('avatar-input');
    var chooseBtn = document.getElementById('avatar-choose-btn');

    if (saveBtn)   saveBtn.addEventListener('click', Profile.save);
    if (bioInput)  bioInput.addEventListener('input', Profile.updateBioCount);
    if (chooseBtn) chooseBtn.addEventListener('click', function () { fileInput.click(); });

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files.length > 0) Profile.previewAvatar(this.files[0]);
        });
    }
});
