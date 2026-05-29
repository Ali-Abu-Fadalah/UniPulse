const Admin = (function () {

    const API = '/unihub/modules/admin.php';

    // UI Helper: Custom Confirmation
    function showConfirm(message, onConfirm) {
        let modal = document.getElementById('admin-confirm-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'admin-confirm-modal';
            modal.className = 'confirm-modal-overlay';
            modal.innerHTML = `
                <div class="confirm-modal">
                    <div class="confirm-modal-icon">⚠️</div>
                    <h3 id="confirm-modal-title">Are you sure?</h3>
                    <p id="confirm-modal-msg"></p>
                    <div class="confirm-modal-actions">
                        <button class="btn-ghost" id="confirm-cancel">Cancel</button>
                        <button class="btn-danger" id="confirm-ok">Delete</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        const msgEl = modal.querySelector('#confirm-modal-msg');
        msgEl.textContent = message;
        modal.classList.add('show');

        const okBtn = modal.querySelector('#confirm-ok');
        const cancelBtn = modal.querySelector('#confirm-cancel');

        const cleanup = () => {
            modal.classList.remove('show');
            okBtn.onclick = null;
            cancelBtn.onclick = null;
        };

        okBtn.onclick = () => { cleanup(); onConfirm(); };
        cancelBtn.onclick = () => { cleanup(); };
    }

    async function loadTab(type) {
        const tbody = document.getElementById('admin-' + type + '-list');
        if (!tbody) return;
        
        const container = document.getElementById('admin-subtab-' + type) || document.getElementById('tab-' + type);
        const colCount = container ? (container.querySelector('thead tr')?.children?.length || 5) : 5;
        
        tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center;">
            <div style="display:flex; align-items:center; justify-content:center; gap:0.5rem; padding:1rem;">
                <div class="spinner"></div> Loading ${type}...
            </div>
        </td></tr>`;

        try {
            const res = await fetch(API + '?type=' + type);
            if (!res.ok) throw new Error('Network response was not ok');
            
            const data = await res.json();
            tbody.innerHTML = '';
            
            if (!data || data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center;color:var(--text-muted);padding:2rem;">No ${type} found.</td></tr>`;
                return;
            }

            if (type === 'users') renderUsers(data, tbody);
            if (type === 'notes') renderNotes(data, tbody);
            if (type === 'skills') renderSkills(data, tbody);
            if (type === 'products') renderProducts(data, tbody);
            if (type === 'events') renderEvents(data, tbody);

        } catch (e) {
            console.error('Admin Load Error:', e);
            tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align:center;color:var(--error);padding:2rem;">Failed to load data.</td></tr>`;
        }
    }

    function renderUsers(users, tbody) {
        const myId = document.getElementById('current-user-id')?.value || '';
        
        users.forEach(u => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors';
            const roleBadge = u.role === 'admin' 
                ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">Admin</span>' 
                : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">User</span>';
                
            let toggleBtn = '';
            if (String(u.id) !== myId) {
                const newRole = u.role === 'admin' ? 'user' : 'admin';
                const btnText = u.role === 'admin' ? 'Remove Admin' : 'Make Admin';
                toggleBtn = `<button type="button" class="btn-toggle-role btn btn-primary btn-sm" data-id="${u.id}" data-role="${newRole}">${btnText}</button>`;
            }
                
            tr.innerHTML = `
                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">${escapeHtml(u.full_name)}</td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">${escapeHtml(u.email)}</td>
                <td class="py-3 px-4">${roleBadge}</td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">${new Date(u.created_at).toLocaleDateString()}</td>
                <td class="py-3 px-4 text-right">
                    ${toggleBtn}
                    <button type="button" class="btn-del btn btn-danger btn-sm" data-id="${u.id}" data-type="user">Delete</button>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    function renderNotes(notes, tbody) {
        notes.forEach(n => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors';
            const snippet = n.content.length > 50 ? n.content.substring(0, 50) + '...' : n.content;
            tr.innerHTML = `
                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">${escapeHtml(n.full_name)}</td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400 max-w-xs truncate" title="${escapeHtml(n.content)}">${escapeHtml(snippet)}</td>
                <td class="py-3 px-4 text-right">
                    <button type="button" class="btn-del btn btn-danger btn-sm" data-id="${n.id}" data-type="note">Delete</button>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    function renderSkills(skills, tbody) {
        skills.forEach(s => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">${escapeHtml(s.full_name)}</td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400"><span class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-xs rounded-md">${escapeHtml(s.offered)}</span></td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400"><span class="inline-block px-2 py-1 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 text-xs rounded-md">${escapeHtml(s.needed)}</span></td>
                <td class="py-3 px-4 text-right">
                    <button type="button" class="btn-del btn btn-danger btn-sm" data-id="${s.id}" data-type="skill">Delete</button>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    function renderProducts(products, tbody) {
        products.forEach(p => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors';
            tr.innerHTML = `
                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">${escapeHtml(p.full_name)}</td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">${escapeHtml(p.title)}</td>
                <td class="py-3 px-4 text-gray-900 dark:text-white font-semibold">$${parseFloat(p.price).toFixed(2)}</td>
                <td class="py-3 px-4 text-right">
                    <button type="button" class="btn-del btn btn-danger btn-sm" data-id="${p.id}" data-type="product">Delete</button>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    function renderEvents(events, tbody) {
        events.forEach(e => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors';
            const formattedDateTime = e.date ? new Date(e.date + 'T' + e.time).toLocaleString('en-US', {
                month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : '—';
            const rsvpCount = e.rsvp_count || 0;
            tr.innerHTML = `
                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">${escapeHtml(e.full_name || e.host_name)}</td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">${escapeHtml(e.title)}</td>
                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">${formattedDateTime}</td>
                <td class="py-3 px-4"><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">${rsvpCount} RSVPs</span></td>
                <td class="py-3 px-4 text-right">
                    <button type="button" class="btn-del btn btn-danger btn-sm" data-id="${e.id}" data-type="event">Delete</button>
                </td>`;
            tbody.appendChild(tr);
        });
    }

    // Consolidated Global Click Listener
    document.addEventListener('click', async function (e) {
        // console.log('Global click:', e.target); // Debug
        
        // 1. DELETE BUTTONS
        const delBtn = e.target.closest('.btn-del');
        if (delBtn) {
            console.log('Delete button clicked:', delBtn.dataset);
            e.preventDefault();
            const { id, type } = delBtn.dataset;
            const tr = delBtn.closest('tr');

            if (!id || !type || !tr) return;

            showConfirm(`Are you sure you want to delete this ${type}?`, async () => {
                delBtn.disabled = true;
                tr.style.opacity = '0.4';
                tr.style.pointerEvents = 'none';

                try {
                    const res = await fetch(API, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete_' + type, id: id })
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        tr.style.transform = 'translateX(20px)';
                        tr.style.opacity = '0';
                        setTimeout(() => tr.remove(), 300);
                    } else {
                        throw new Error(data.error || 'Failed to delete');
                    }
                } catch (err) {
                    console.error('Delete error:', err);
                    alert(err.message);
                    delBtn.disabled = false;
                    tr.style.opacity = '1';
                    tr.style.pointerEvents = 'auto';
                }
            });
            return;
        }

        // 2. TOGGLE ROLE BUTTONS
        const roleBtn = e.target.closest('.btn-toggle-role');
        if (roleBtn) {
            e.preventDefault();
            const { id, role } = roleBtn.dataset;
            if (!id || !role) return;

            roleBtn.disabled = true;
            roleBtn.innerHTML = 'Updating...';

            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle_admin', id: id, role: role })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    loadTab('users');
                } else {
                    throw new Error(data.error || 'Failed to update role');
                }
            } catch (err) {
                console.error('Role toggle error:', err);
                alert(err.message);
                roleBtn.disabled = false;
                roleBtn.innerHTML = role === 'admin' ? 'Make Admin' : 'Remove Admin';
            }
            return;
        }
    });

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    return { loadTab };

})();
