const Notes = (function () {
    'use strict';
    const API = 'modules/notes.php';
    let editId = null;

    async function load() {
        const list = document.getElementById('notes-list');
        const counter = document.getElementById('notes-count');
        list.innerHTML = 
            '<div class="note-card skeleton" style="height: 120px; margin-bottom: 1rem;"></div>' +
            '<div class="note-card skeleton" style="height: 80px; margin-bottom: 1rem;"></div>';

        try {
            const res  = await fetch(API);
            const data = await res.json();

            list.innerHTML = '';

            if (data.notes.length === 0) {
                list.innerHTML = '<div class="notes-empty"><span>📝</span><p>No notes yet. Write your first one above.</p></div>';
                counter.textContent = '0 notes';
                return;
            }

            counter.textContent = data.notes.length + (data.notes.length === 1 ? ' note' : ' notes');

            data.notes.forEach(function (note) {
                list.appendChild(buildCard(note));
            });
        } catch (e) {
            list.innerHTML = '<div class="notes-empty"><span>⚠️</span><p>Couldn’t load your notes. Please refresh.</p></div>';
        }
    }

    function buildCard(note) {
        const card = document.createElement('div');
        card.className = 'note-card';
        card.dataset.id = note.id;

        const date = new Date(note.created_at).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        card.innerHTML =
            '<div class="note-content">' + escapeHtml(note.content) + '</div>' +
            '<div class="note-footer">' +
                '<span class="note-date">' + date + '</span>' +
                '<div style="display:flex;gap:0.5rem;">' +
                    '<button class="note-edit-btn btn-ghost btn-sm" data-id="' + note.id + '" title="Edit note" style="padding:0.25rem;color:var(--text-muted);">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>' +
                    '</button>' +
                    '<button class="note-delete-btn" data-id="' + note.id + '" title="Delete note">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>' +
                    '</button>' +
                '</div>' +
            '</div>';

        card.querySelector('.note-delete-btn').addEventListener('click', function () {
            deleteNote(note.id, card);
        });

        card.querySelector('.note-edit-btn').addEventListener('click', function () {
            editId = note.id;
            document.getElementById('note-input').value = note.content;
            document.getElementById('note-add-btn').textContent = 'Save Changes';
            updateCharCount();
            document.getElementById('note-input').focus();
        });

        return card;
    }

    async function add() {
        const textarea = document.getElementById('note-input');
        const btn      = document.getElementById('note-add-btn');
        const error    = document.getElementById('note-error');
        const content  = textarea.value.trim();

        error.textContent = '';

        if (!content) {
            error.textContent = 'Write something before saving.';
            textarea.focus();
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Saving…';

        try {
            const res  = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(editId ? { action: 'edit', id: editId, content: content } : { action: 'add', content: content })
            });
            const data = await res.json();

            if (!res.ok) {
                error.textContent = data.error || 'Something went wrong. Try again.';
                return;
            }

            textarea.value = '';
            btn.textContent = 'Save Note';
            updateCharCount();

            let oldCardId = editId;
            editId = null;

            if (oldCardId) {
                // Remove old card if editing
                const oldCard = document.querySelector(`.note-card[data-id="${oldCardId}"]`);
                if (oldCard) oldCard.remove();
            }

            const list = document.getElementById('notes-list');
            const empty = list.querySelector('.notes-empty');
            if (empty) empty.remove();

            list.insertBefore(buildCard(data.note), list.firstChild);

            if (!oldCardId) {
                const counter = document.getElementById('notes-count');
                const current = parseInt(counter.textContent) || 0;
                counter.textContent = (current + 1) + ((current + 1) === 1 ? ' note' : ' notes');
            }

        } catch (e) {
            error.textContent = 'Connection error. Please try again.';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Note';
        }
    }

    async function deleteNote(id, card) {
        card.style.opacity = '0.4';
        card.style.pointerEvents = 'none';

        try {
            const res  = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const data = await res.json();

            if (!res.ok) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                alert(data.error || 'Could not delete note.');
                return;
            }

            card.style.transition = 'all 0.25s ease';
            card.style.transform  = 'scale(0.95)';
            card.style.opacity    = '0';

            setTimeout(function () {
                card.remove();
                const list = document.getElementById('notes-list');
                const counter = document.getElementById('notes-count');
                const remaining = list.querySelectorAll('.note-card').length;

                counter.textContent = remaining + (remaining === 1 ? ' note' : ' notes');

                if (remaining === 0) {
                    list.innerHTML = '<div class="notes-empty"><span>📝</span><p>No notes yet. Write your first one above.</p></div>';
                    counter.textContent = '0 notes';
                }
            }, 260);

        } catch (e) {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            alert('Network error. Try again.');
        }
    }

    function updateCharCount() {
        const textarea = document.getElementById('note-input');
        const counter  = document.getElementById('char-count');
        const len      = textarea.value.length;
        counter.textContent = len + ' / 1000';
        counter.style.color = len > 900 ? 'var(--error)' : 'var(--text-muted)';
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/\n/g, '<br>');
    }

    return { load, add, updateCharCount };

})();

document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('note-input');
    const addBtn   = document.getElementById('note-add-btn');

    if (!textarea) return;

    textarea.addEventListener('input', Notes.updateCharCount);

    textarea.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            Notes.add();
        }
    });

    addBtn.addEventListener('click', Notes.add);
});
