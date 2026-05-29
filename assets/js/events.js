const Events = (function () {

    const API = '/unihub/modules/events.php';
    let currentView = 'browse'; // 'browse' or 'host'
    let editId = null;

    async function load(query = '') {
        setLoading(true);
        try {
            const url = query ? API + '?q=' + encodeURIComponent(query) : API;
            const res = await fetch(url);
            const data = await res.json();

            if (!res.ok) throw new Error(data.error || 'Failed to fetch events');

            renderEvents(data.events || []);
        } catch (e) {
            console.error(e);
            showError('Unable to load events. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    function renderEvents(events) {
        const list = document.getElementById('events-list');
        const myEventsList = document.getElementById('my-events-list');
        const myId = document.getElementById('current-user-id')?.value || '';

        if (list) list.innerHTML = '';
        if (myEventsList) myEventsList.innerHTML = '';

        if (events.length === 0) {
            const emptyHTML = `
                <div class="events-empty-state">
                    <span>🗓️</span>
                    <p>No upcoming events found.</p>
                </div>`;
            if (list && currentView === 'browse') list.innerHTML = emptyHTML;
            if (myEventsList && currentView === 'host') myEventsList.innerHTML = '<p class="market-empty-msg">You have not hosted any events yet.</p>';
            return;
        }

        let myEventsCount = 0;

        events.forEach(e => {
            const isHost = String(e.user_id) === myId;
            const formattedDate = formatDate(e.date);
            const rsvpText = e.user_rsvp ? 'Attending ✓' : 'Join Event';
            const rsvpClass = e.user_rsvp ? 'btn-success btn-rsvp-active' : 'btn-outline';

            // Create Event Card for main list
            if (list && currentView === 'browse') {
                const card = document.createElement('div');
                card.className = 'event-card';
                card.innerHTML = `
                    <div class="event-card-header">
                        <div class="event-badge">📅 ${formattedDate}</div>
                        <div class="event-time">⏰ ${escapeHtml(e.time)}</div>
                    </div>
                    <h3 class="event-card-title">${escapeHtml(e.title)}</h3>
                    <div class="event-card-loc">📍 <strong>${escapeHtml(e.location)}</strong></div>
                    <p class="event-card-desc">${escapeHtml(e.description)}</p>
                    <div class="event-card-footer">
                        <span class="event-card-host">Host: ${escapeHtml(e.host_name)}</span>
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <span class="event-rsvp-count" id="rsvp-count-${e.id}" style="cursor:pointer; text-decoration:underline;" title="View Attendees">👥 ${e.rsvp_count} going</span>
                            <button class="btn ${rsvpClass} btn-sm rsvp-btn" data-id="${e.id}">
                                ${rsvpText}
                            </button>
                        </div>
                    </div>
                `;

                card.querySelector('.rsvp-btn').addEventListener('click', function () {
                    toggleRsvp(e.id, this);
                });
                card.querySelector(`#rsvp-count-${e.id}`).addEventListener('click', function () {
                    showAttendees(e.id, e.title);
                });

                list.appendChild(card);
            }

            // Create list for "My Hosted Events"
            if (isHost) {
                myEventsCount++;
                if (myEventsList && currentView === 'host') {
                    const row = document.createElement('div');
                    row.className = 'market-listing-row';
                    row.dataset.id = e.id;
                    row.innerHTML = `
                        <div class="listing-thumb placeholder">📅</div>
                        <div class="listing-info">
                            <div class="listing-title">${escapeHtml(e.title)}</div>
                            <div class="listing-price" style="font-size:0.8rem; font-weight:normal; color:var(--text-muted);">
                                ${formattedDate} @ ${escapeHtml(e.time)} | 👥 ${e.rsvp_count} RSVP'd
                            </div>
                        </div>
                        <div style="display:flex;gap:0.25rem;">
                            <button class="listing-edit-btn" data-id="${e.id}" title="Edit Event" style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0.25rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                            </button>
                            <button class="listing-del-btn" data-id="${e.id}" title="Cancel Event">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </div>
                    `;

                    row.querySelector('.listing-del-btn').addEventListener('click', function () {
                        deleteEvent(e.id, row);
                    });

                    row.querySelector('.listing-edit-btn').addEventListener('click', function () {
                        editId = e.id;
                        document.getElementById('event-title').value = e.title;
                        document.getElementById('event-description').value = e.description;
                        document.getElementById('event-date').value = e.date;
                        document.getElementById('event-time').value = e.time;
                        document.getElementById('event-location').value = e.location;
                        document.getElementById('event-add-btn').textContent = 'Save Changes';
                        document.getElementById('event-title').focus();
                    });

                    myEventsList.appendChild(row);
                }
            }
        });

        if (myEventsList && currentView === 'host' && myEventsCount === 0) {
            myEventsList.innerHTML = '<p class="market-empty-msg">You have not hosted any events yet.</p>';
        }
    }

    async function add() {
        const titleInput = document.getElementById('event-title');
        const descInput  = document.getElementById('event-description');
        const dateInput  = document.getElementById('event-date');
        const timeInput  = document.getElementById('event-time');
        const locInput   = document.getElementById('event-location');
        const btn        = document.getElementById('event-add-btn');
        const error      = document.getElementById('event-error');

        const title = titleInput.value.trim();
        const desc  = descInput.value.trim();
        const date  = dateInput.value.trim();
        const time  = timeInput.value.trim();
        const loc   = locInput.value.trim();

        error.textContent = '';
        error.className = 'market-error';

        if (!title || !desc || !date || !time || !loc) {
            error.textContent = 'Please fill out all fields.';
            error.classList.add('show');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Scheduling…';

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(Object.assign(
                    { action: editId ? 'edit' : 'add', title: title, description: desc, date: date, time: time, location: loc },
                    editId ? { id: editId } : {}
                ))
            });
            const data = await res.json();

            if (!res.ok) {
                error.textContent = data.error || 'Failed to schedule event.';
                error.classList.add('show');
                return;
            }

            // Clear inputs
            titleInput.value = '';
            descInput.value = '';
            dateInput.value = '';
            timeInput.value = '';
            locInput.value = '';
            editId = null;
            btn.textContent = 'Host Event';

            // Reload events list
            await load();

            error.textContent = '✓ Event hosted successfully!';
            error.className = 'market-error success show';
            setTimeout(() => {
                error.classList.remove('show');
            }, 3000);

        } catch (e) {
            error.textContent = 'Connection error. Please try again.';
            error.classList.add('show');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Host Event';
        }
    }

    async function toggleRsvp(eventId, button) {
        button.disabled = true;
        
        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'rsvp', event_id: eventId })
            });
            const data = await res.json();

            if (res.ok) {
                // Update button state
                if (data.is_attending) {
                    button.textContent = 'Attending ✓';
                    button.className = 'btn btn-success btn-rsvp-active btn-sm rsvp-btn';
                } else {
                    button.textContent = 'Join Event';
                    button.className = 'btn btn-outline btn-sm rsvp-btn';
                }
                
                // Update RSVP counter
                const counter = document.getElementById(`rsvp-count-${eventId}`);
                if (counter) {
                    counter.textContent = `👥 ${data.rsvp_count} going`;
                }
            } else {
                alert(data.error || 'Failed to toggle RSVP.');
            }
        } catch (e) {
            alert('Connection error. Try again.');
        } finally {
            button.disabled = false;
        }
    }

    async function deleteEvent(id, element) {
        if (!confirm('Are you sure you want to cancel this event?')) return;

        element.style.opacity = '0.4';
        element.style.pointerEvents = 'none';

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const data = await res.json();

            if (!res.ok) {
                element.style.opacity = '1';
                element.style.pointerEvents = 'auto';
                alert(data.error || 'Failed to cancel event.');
                return;
            }

            element.style.transition = 'all 0.25s ease';
            element.style.transform = 'scale(0.95)';
            element.style.opacity = '0';
            setTimeout(() => {
                element.remove();
                load();
            }, 250);

        } catch (e) {
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
            alert('Network error. Try again.');
        }
    }

    function setView(view) {
        currentView = view;
        document.querySelectorAll('.events-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });

        const browseView = document.getElementById('events-view-browse');
        const hostView   = document.getElementById('events-view-host');

        if (view === 'browse') {
            if (browseView) browseView.style.display = 'block';
            if (hostView) hostView.style.display = 'none';
        } else {
            if (browseView) browseView.style.display = 'none';
            if (hostView) hostView.style.display = 'grid';
        }
        load();
    }

    function setLoading(on) {
        const list = document.getElementById('events-list');
        if (on && list && currentView === 'browse') {
            list.innerHTML = `
                <div class="event-card skeleton" style="height: 160px; margin-bottom: 1rem;"></div>
                <div class="event-card skeleton" style="height: 160px; margin-bottom: 1rem;"></div>`;
        }
    }

    function showError(msg) {
        const list = document.getElementById('events-list');
        if (list) {
            list.innerHTML = `<div class="events-empty-state"><span>⚠️</span><p>${escapeHtml(msg)}</p></div>`;
        }
    }

    function formatDate(str) {
        if (!str) return '—';
        const d = new Date(str);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    async function showAttendees(eventId, eventTitle) {
        let modal = document.getElementById('attendees-popup');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'attendees-popup';
            modal.className = 'profile-popup-overlay';
            document.body.appendChild(modal);
        }

        modal.innerHTML =
            '<div class="profile-popup-card" style="max-height: 80vh; overflow-y: auto;">' +
                '<button class="profile-popup-close" id="attendees-popup-close">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                '</button>' +
                '<h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Attendees for ' + escapeHtml(eventTitle) + '</h3>' +
                '<div id="attendees-list-content">Loading...</div>' +
            '</div>';
        
        modal.classList.add('show');
        modal.querySelector('#attendees-popup-close').addEventListener('click', () => modal.classList.remove('show'));
        modal.onclick = (e) => { if (e.target === modal) modal.classList.remove('show'); };

        try {
            const res = await fetch(API + '?action=get_attendees&event_id=' + eventId);
            const data = await res.json();
            
            let html = '';
            if (!data.attendees || data.attendees.length === 0) {
                html = '<p style="color:var(--text-muted);">No one has RSVP\\'d yet.</p>';
            } else {
                data.attendees.forEach(u => {
                    let avatarHtml = u.avatar
                        ? '<div style="width:32px;height:32px;border-radius:50%;background-image:url(' + escapeHtml(u.avatar) + ');background-size:cover;background-position:center;flex-shrink:0;"></div>'
                        : '<div style="width:32px;height:32px;border-radius:50%;background-color:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;flex-shrink:0;">' + (u.full_name ? u.full_name.charAt(0).toUpperCase() : '?') + '</div>';
                    html += '<div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid var(--border);">' +
                        avatarHtml +
                        '<span>' + escapeHtml(u.full_name) + '</span>' +
                        '</div>';
                });
            }
            modal.querySelector('#attendees-list-content').innerHTML = html;
        } catch (e) {
            modal.querySelector('#attendees-list-content').innerHTML = '<p class="market-error show">Unable to load attendees.</p>';
        }
    }

    return { load, setView, add };

})();

document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('event-add-btn');
    if (addBtn) addBtn.addEventListener('click', Events.add);

    document.querySelectorAll('.events-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            Events.setView(this.dataset.view);
        });
    });
});
