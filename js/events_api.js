// Practical 13 - Asynchronous AJAX / Fetch API Event Management CRUD
document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('apiEventsTableBody');
    const searchInput = document.getElementById('apiSearchInput');
    const categoryFilter = document.getElementById('apiCategoryFilter');
    const sortSelect = document.getElementById('apiSortSelect');
    const alertBox = document.getElementById('apiAlertBox');
    const refreshBtn = document.getElementById('refreshBtn');
    const countBadge = document.getElementById('apiEventCount');

    // Modals & Forms
    const addEventModalEl = document.getElementById('addEventModal');
    const addEventModal = addEventModalEl ? new bootstrap.Modal(addEventModalEl) : null;
    const addEventForm = document.getElementById('addEventForm');
    const addSubmitBtn = document.getElementById('addSubmitBtn');

    const editEventModalEl = document.getElementById('editEventModal');
    const editEventModal = editEventModalEl ? new bootstrap.Modal(editEventModalEl) : null;
    const editEventForm = document.getElementById('editEventForm');
    const editSubmitBtn = document.getElementById('editSubmitBtn');

    function showAlert(message, type = 'success') {
        if (!alertBox) return;
        alertBox.className = `alert alert-${type} alert-dismissible fade show py-2 px-3 mb-3`;
        alertBox.innerHTML = `
            ${message}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        `;
        alertBox.classList.remove('d-none');
        setTimeout(() => {
            alertBox.classList.add('d-none');
        }, 4000);
    }

    async function loadEvents() {
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm me-2"></div> Loading events from MySQL API...</td></tr>`;
        }

        const query = searchInput ? encodeURIComponent(searchInput.value.trim()) : '';
        const cat = categoryFilter ? encodeURIComponent(categoryFilter.value) : '';
        const sort = sortSelect ? encodeURIComponent(sortSelect.value) : 'earliest';

        try {
            const res = await fetch(`../api/events.php?search=${query}&category=${cat}&sort=${sort}`);
            const data = await res.json();
            if (data.success) {
                renderTable(data.data);
                if (countBadge) countBadge.textContent = `${data.count} event(s) loaded from MySQL API`;
            } else {
                showAlert(data.error || 'Failed to fetch events', 'danger');
            }
        } catch (err) {
            console.error('API Fetch error:', err);
            showAlert('Network error while querying events API.', 'danger');
        }
    }

    function renderTable(events) {
        if (!tableBody) return;
        if (events.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No events found matching your filter criteria.</td></tr>`;
            return;
        }

        tableBody.innerHTML = events.map(ev => {
            const badgeClass = getCategoryBadgeClass(ev.category);
            return `
                <tr>
                    <td class="fw-semibold">${escapeHtml(ev.title)}</td>
                    <td class="text-nowrap">${escapeHtml(ev.event_date)}</td>
                    <td>${escapeHtml(ev.venue)}</td>
                    <td><span class="badge ${badgeClass}">${escapeHtml(ev.category)}</span></td>
                    <td><span class="badge bg-success-subtle text-success border border-success-subtle">upcoming</span></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1 btn-edit-event" data-event='${JSON.stringify(ev).replace(/'/g, "&apos;")}'>Edit</button>
                        <button class="btn btn-sm btn-outline-danger py-0 px-2 btn-delete-event" data-id="${ev.id}" data-title="${escapeHtml(ev.title)}">Delete</button>
                    </td>
                </tr>
            `;
        }).join('');

        // Bind Edit buttons
        document.querySelectorAll('.btn-edit-event').forEach(btn => {
            btn.addEventListener('click', () => {
                const evData = JSON.parse(btn.getAttribute('data-event'));
                document.getElementById('editEventId').value = evData.id;
                document.getElementById('editEventTitle').value = evData.title;
                document.getElementById('editEventDate').value = evData.event_date;
                document.getElementById('editEventVenue').value = evData.venue;
                document.getElementById('editEventCategory').value = evData.category;
                document.getElementById('editEventDescription').value = evData.description || '';
                if (editEventModal) editEventModal.show();
            });
        });

        // Bind Delete buttons
        document.querySelectorAll('.btn-delete-event').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const title = btn.getAttribute('data-title');
                if (confirm(`Are you sure you want to delete "${title}"? This change will be saved in MySQL immediately via API.`)) {
                    await deleteEvent(id);
                }
            });
        });
    }

    async function deleteEvent(id) {
        try {
            const res = await fetch('../api/events.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: parseInt(id) })
            });
            const data = await res.json();
            if (data.success) {
                showAlert(data.message, 'success');
                loadEvents();
            } else {
                showAlert(data.error || 'Failed to delete event.', 'danger');
            }
        } catch (err) {
            showAlert('Network error while deleting event.', 'danger');
        }
    }

    // Add Event Submit
    if (addEventForm) {
        addEventForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (addSubmitBtn) {
                addSubmitBtn.disabled = true;
                addSubmitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Saving...`;
            }

            const payload = {
                action: 'create',
                title: document.getElementById('addEventTitle').value.trim(),
                event_date: document.getElementById('addEventDate').value,
                venue: document.getElementById('addEventVenue').value.trim(),
                category: document.getElementById('addEventCategory').value,
                description: document.getElementById('addEventDescription').value.trim()
            };

            try {
                const res = await fetch('../api/events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showAlert(data.message, 'success');
                    if (addEventModal) addEventModal.hide();
                    addEventForm.reset();
                    loadEvents();
                } else {
                    showAlert(data.error || 'Failed to add event.', 'danger');
                }
            } catch (err) {
                showAlert('Network error while creating event.', 'danger');
            } finally {
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = false;
                    addSubmitBtn.textContent = 'Save Event';
                }
            }
        });
    }

    // Edit Event Submit
    if (editEventForm) {
        editEventForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (editSubmitBtn) {
                editSubmitBtn.disabled = true;
                editSubmitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Updating...`;
            }

            const payload = {
                action: 'update',
                id: parseInt(document.getElementById('editEventId').value),
                title: document.getElementById('editEventTitle').value.trim(),
                event_date: document.getElementById('editEventDate').value,
                venue: document.getElementById('editEventVenue').value.trim(),
                category: document.getElementById('editEventCategory').value,
                description: document.getElementById('editEventDescription').value.trim()
            };

            try {
                const res = await fetch('../api/events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showAlert(data.message, 'success');
                    if (editEventModal) editEventModal.hide();
                    loadEvents();
                } else {
                    showAlert(data.error || 'Failed to update event.', 'danger');
                }
            } catch (err) {
                showAlert('Network error while updating event.', 'danger');
            } finally {
                if (editSubmitBtn) {
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.textContent = 'Update Event';
                }
            }
        });
    }

    function getCategoryBadgeClass(category) {
        switch(category) {
            case 'Workshop': return 'badge-workshop';
            case 'Competition': return 'badge-competition';
            case 'Social': return 'badge-social';
            case 'Academic': return 'badge-academic';
            case 'Seminar': return 'badge-seminar';
            case 'Sports': return 'badge-sports';
            case 'Cultural': return 'badge-cultural';
            default: return 'bg-secondary';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Filters event listeners
    if (searchInput) searchInput.addEventListener('input', loadEvents);
    if (categoryFilter) categoryFilter.addEventListener('change', loadEvents);
    if (sortSelect) sortSelect.addEventListener('change', loadEvents);
    if (refreshBtn) refreshBtn.addEventListener('click', loadEvents);

    loadEvents();
});
