const Marketplace = (function () {
    'use strict';
    const API = 'modules/marketplace.php';
    let currentView = 'browse'; // 'browse' or 'sell'
    let editId = null;

    async function load(query = '') {
        setLoading(true);
        try {
            const isMyListings = currentView === 'sell';
            let url = API + '?my_listings=' + (isMyListings ? '1' : '0');
            if (query && !isMyListings) {
                url += '&q=' + encodeURIComponent(query);
            }

            const res = await fetch(url);
            const data = await res.json();

            if (!res.ok) throw new Error(data.error || 'Failed to fetch products');

            if (isMyListings) {
                renderMyListings(data.products || []);
            } else {
                renderBrowseGrid(data.products || []);
            }
        } catch (e) {
            console.error(e);
            showError('Unable to load products. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    function renderBrowseGrid(products) {
        const grid = document.getElementById('market-products-grid');
        if (!grid) return;
        grid.innerHTML = '';

        if (products.length === 0) {
            grid.innerHTML = `
                <div class="market-empty-state">
                    <span>🛒</span>
                    <p>No products found in the marketplace.</p>
                </div>`;
            return;
        }

        products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'market-card';

            const imgHTML = p.image 
                ? `<div class="market-card-img" style="background-image: url('${escapeHtml(p.image)}');"></div>`
                : `<div class="market-card-img placeholder">📦 <span>No Image</span></div>`;

            const initial = p.seller_name ? p.seller_name.charAt(0).toUpperCase() : '?';
            const avatarHTML = p.seller_avatar
                ? `<div class="seller-avatar" style="background-image: url('${escapeHtml(p.seller_avatar)}');"></div>`
                : `<div class="seller-avatar">${initial}</div>`;

            card.innerHTML = `
                ${imgHTML}
                <div class="market-card-body">
                    <div class="market-card-price">$${parseFloat(p.price).toFixed(2)}</div>
                    <h4 class="market-card-title">${escapeHtml(p.title)}</h4>
                    <p class="market-card-desc">${escapeHtml(p.description)}</p>
                    <div class="market-card-footer">
                        <div class="market-card-seller">
                            ${avatarHTML}
                            <span class="seller-name">${escapeHtml(p.seller_name)}</span>
                        </div>
                        <button class="btn btn-primary btn-sm market-contact-btn" data-uid="${p.user_id}" data-name="${escapeHtml(p.seller_name)}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                            <span>Chat</span>
                        </button>
                    </div>
                </div>
            `;

            card.querySelector('.market-contact-btn').addEventListener('click', function (e) {
                e.stopPropagation();
                const uid = parseInt(this.dataset.uid);
                const name = this.dataset.name;
                if (typeof Messages !== 'undefined') {
                    Messages.openWithUser(uid, name);
                }
            });

            grid.appendChild(card);
        });
    }

    function renderMyListings(products) {
        const list = document.getElementById('my-listings-list');
        if (!list) return;
        list.innerHTML = '';

        if (products.length === 0) {
            list.innerHTML = '<p class="market-empty-msg">You have not listed any items for sale yet.</p>';
            return;
        }

        products.forEach(p => {
            const row = document.createElement('div');
            row.className = 'market-listing-row';
            row.dataset.id = p.id;

            const imgHTML = p.image
                ? `<div class="listing-thumb" style="background-image: url('${escapeHtml(p.image)}');"></div>`
                : `<div class="listing-thumb placeholder">📦</div>`;

            row.innerHTML = `
                ${imgHTML}
                <div class="listing-info">
                    <div class="listing-title">${escapeHtml(p.title)}</div>
                    <div class="listing-price">$${parseFloat(p.price).toFixed(2)}</div>
                </div>
                <div style="display:flex;gap:0.25rem;">
                    <button class="listing-edit-btn" data-id="${p.id}" title="Edit Listing" style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0.25rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                    </button>
                    <button class="listing-del-btn" data-id="${p.id}" title="Remove Listing">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </div>
            `;

            row.querySelector('.listing-del-btn').addEventListener('click', function () {
                deleteProduct(p.id, row);
            });

            row.querySelector('.listing-edit-btn').addEventListener('click', function () {
                editId = p.id;
                document.getElementById('market-title').value = p.title;
                document.getElementById('market-price').value = p.price;
                document.getElementById('market-description').value = p.description;
                document.getElementById('market-add-btn').textContent = 'Save Changes';
                document.getElementById('market-title').focus();
            });

            list.appendChild(row);
        });
    }

    async function add() {
        const titleInput = document.getElementById('market-title');
        const priceInput = document.getElementById('market-price');
        const descInput  = document.getElementById('market-description');
        const imageFile  = document.getElementById('market-image');
        const btn        = document.getElementById('market-add-btn');
        const error      = document.getElementById('market-error');

        const title = titleInput.value.trim();
        const price = parseFloat(priceInput.value);
        const desc  = descInput.value.trim();

        error.textContent = '';
        error.className = 'market-error';

        if (!title || isNaN(price) || price <= 0 || !desc) {
            error.textContent = 'Please fill out all fields. Price must be greater than 0.';
            error.classList.add('show');
            return;
        }

        const formData = new FormData();
        formData.append('action', editId ? 'edit' : 'add');
        if (editId) formData.append('id', editId);
        formData.append('title', title);
        formData.append('price', price);
        formData.append('description', desc);
        if (imageFile.files.length > 0) {
            formData.append('image', imageFile.files[0]);
        }

        btn.disabled = true;
        btn.textContent = 'Posting…';

        try {
            const res = await fetch(API, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (!res.ok) {
                error.textContent = data.error || 'Failed to post item.';
                error.classList.add('show');
                return;
            }

            // Clear inputs
            titleInput.value = '';
            priceInput.value = '';
            descInput.value = '';
            imageFile.value = '';
            const filenameEl = document.getElementById('market-filename');
            if (filenameEl) filenameEl.textContent = '';
            editId = null;

            // Reload listings
            await load();
            
            // Switch to Browse tab or show success message
            error.textContent = '✓ Item listed successfully!';
            error.className = 'market-error success show';
            setTimeout(() => {
                error.classList.remove('show');
            }, 3000);

        } catch (e) {
            error.textContent = 'Connection error. Please try again.';
            error.classList.add('show');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Post Item';
        }
    }

    async function deleteProduct(id, element) {
        if (!confirm('Are you sure you want to remove this listing?')) return;

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
                alert(data.error || 'Failed to remove listing.');
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
        document.querySelectorAll('.market-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });

        const browseView = document.getElementById('market-view-browse');
        const sellView   = document.getElementById('market-view-sell');

        if (view === 'browse') {
            if (browseView) browseView.style.display = 'block';
            if (sellView) sellView.style.display = 'none';
        } else {
            if (browseView) browseView.style.display = 'none';
            if (sellView) sellView.style.display = 'grid';
        }
        load();
    }

    function setLoading(on) {
        const grid = document.getElementById('market-products-grid');
        const list = document.getElementById('my-listings-list');
        
        if (on) {
            const skeletons = `
                <div class="market-card skeleton" style="height: 250px;"></div>
                <div class="market-card skeleton" style="height: 250px;"></div>
                <div class="market-card skeleton" style="height: 250px;"></div>`;
            if (grid && currentView === 'browse') grid.innerHTML = skeletons;
            if (list && currentView === 'sell') list.innerHTML = '<p class="market-empty-msg">Loading listings...</p>';
        }
    }

    function showError(msg) {
        const grid = document.getElementById('market-products-grid');
        if (grid) {
            grid.innerHTML = `<div class="market-empty-state"><span>⚠️</span><p>${escapeHtml(msg)}</p></div>`;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return { load, setView, add };

})();

document.addEventListener('DOMContentLoaded', function () {
    const sellBtn = document.getElementById('market-add-btn');
    if (sellBtn) sellBtn.addEventListener('click', Marketplace.add);

    document.querySelectorAll('.market-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            Marketplace.setView(this.dataset.view);
        });
    });

    const chooseBtn = document.getElementById('market-choose-btn');
    const fileInput = document.getElementById('market-image');
    const filenameEl = document.getElementById('market-filename');

    if (chooseBtn && fileInput) {
        chooseBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                filenameEl.textContent = this.files[0].name;
            } else {
                filenameEl.textContent = '';
            }
        });
    }
});
