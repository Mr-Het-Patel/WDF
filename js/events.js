// Practical 6 - Rendering External JSON via Fetch API, Live Search, Filter, Sort, and Pagination
document.addEventListener('DOMContentLoaded', () => {
    let allEvents = [];
    let filteredEvents = [];
    let currentPage = 1;
    const perPage = 5;

    const eventsTableBody = document.getElementById('eventsTableBody');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const searchInput = document.getElementById('searchEventsInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortBy = document.getElementById('sortBy');
    const paginationControls = document.getElementById('paginationControls');
    const totalCountText = document.getElementById('totalCountText');
    const emptyState = document.getElementById('emptyState');

    // 1. Fetch JSON asynchronously
    async function fetchEvents() {
        if (loadingSpinner) loadingSpinner.classList.remove('d-none');
        if (eventsTableBody) eventsTableBody.innerHTML = '';
        if (emptyState) emptyState.classList.add('d-none');

        try {
            const response = await fetch('../data/events.json');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            allEvents = await response.json();
            applyFilterAndSearch();
        } catch (error) {
            console.error('Fetch error:', error);
            if (eventsTableBody) {
                eventsTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Failed to load events data.</td></tr>`;
            }
        } finally {
            if (loadingSpinner) loadingSpinner.classList.add('d-none');
        }
    }

    // 2. Filter, Search and Sort Logic
    function applyFilterAndSearch() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedCat = categoryFilter ? categoryFilter.value : 'All Categories';
        const sortOrder = sortBy ? sortBy.value : 'earliest';

        filteredEvents = allEvents.filter(ev => {
            const matchesQuery = ev.title.toLowerCase().includes(query) ||
                                 ev.venue.toLowerCase().includes(query) ||
                                 ev.description.toLowerCase().includes(query);
            const matchesCategory = (selectedCat === 'All Categories' || ev.category === selectedCat);
            return matchesQuery && matchesCategory;
        });

        // Sorting
        filteredEvents.sort((a, b) => {
            if (sortOrder === 'latest') {
                return new Date(b.date) - new Date(a.date);
            } else if (sortOrder === 'name') {
                return a.title.localeCompare(b.title);
            } else {
                return new Date(a.date) - new Date(b.date);
            }
        });

        currentPage = 1;
        updatePaginationControls();
        renderEvents();
    }

    // 3. Render Paginated Events
    function renderEvents() {
        if (!eventsTableBody) return;

        if (totalCountText) {
            totalCountText.textContent = `${filteredEvents.length} event(s) found`;
        }

        if (filteredEvents.length === 0) {
            eventsTableBody.innerHTML = '';
            if (emptyState) emptyState.classList.remove('d-none');
            return;
        }

        if (emptyState) emptyState.classList.add('d-none');

        const startIndex = (currentPage - 1) * perPage;
        const pageItems = filteredEvents.slice(startIndex, startIndex + perPage);

        eventsTableBody.innerHTML = pageItems.map(ev => {
            const catBadgeClass = getBadgeClass(ev.category);
            return `
                <tr>
                    <td class="fw-semibold">${escapeHtml(ev.title)}</td>
                    <td class="text-nowrap">${escapeHtml(ev.date)}</td>
                    <td>${escapeHtml(ev.venue)}</td>
                    <td><span class="badge ${catBadgeClass}">${escapeHtml(ev.category)}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="alert('Event Details:\\n${escapeHtml(ev.title)}\\nVenue: ${escapeHtml(ev.venue)}\\n\\n${escapeHtml(ev.description)}')">
                            View Details
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function getBadgeClass(category) {
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

    // 4. Interactive Pagination Controls
    function updatePaginationControls() {
        if (!paginationControls) return;
        const totalPages = Math.ceil(filteredEvents.length / perPage);

        if (totalPages <= 1) {
            paginationControls.innerHTML = '';
            return;
        }

        let html = '';
        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" data-page="${i}">${i}</button>
                </li>
            `;
        }
        paginationControls.innerHTML = html;

        paginationControls.querySelectorAll('.page-link').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = parseInt(btn.getAttribute('data-page'));
                updatePaginationControls();
                renderEvents();
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Event Listeners for Filters
    if (searchInput) searchInput.addEventListener('input', applyFilterAndSearch);
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilterAndSearch);
    if (sortBy) sortBy.addEventListener('change', applyFilterAndSearch);

    // Initial Load
    fetchEvents();
});
