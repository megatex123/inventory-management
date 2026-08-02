<template>
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <router-link to="/serve/create" class="btn btn-primary ml-3">Add QuiviServe</router-link>
                                    <h5 class="m-0 font-weight-bold text-primary">QuiviServe List</h5>
                                </div>

                                <!-- Filter Section -->
                                <div class="row px-3 mt-3">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="m-0 font-weight-bold text-primary">
                                                            <i class="fas fa-filter mr-2"></i>Filters
                                                        </h6>
                                                    </div>
                                                    <div class="col-md-6 text-right">
                                                        <button
                                                            @click="showFilters = !showFilters"
                                                            class="btn btn-sm btn-outline-secondary"
                                                        >
                                                            <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-filter'"></i>
                                                            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <transition name="filter-panel">
                                                <div v-if="showFilters">
                                                <div class="row mt-2">
                                                    <div class="col-md-12 text-right mb-2">
                                                        <button
                                                            @click="clearFilters"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            :disabled="!hasActiveFilters"
                                                        >
                                                            <i class="fas fa-times mr-1"></i>Clear Filters
                                                        </button>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <column-search-panel
                                                            :columns="filterColumns"
                                                            v-model="filters"
                                                            :visible="true"
                                                        />
                                                    </div>
                                                </div>

                                                <div class="row mt-2">
                                                    <!-- Code Filter -->
                                                    <div class="col-md-3 mb-2">
                                                        <label class="small font-weight-bold text-muted">Code Starts With</label>
                                                        <select
                                                            v-model="filters.codeStartsWith"
                                                            class="form-control form-control-sm"
                                                            @change="applyFilters"
                                                        >
                                                            <option value="">All Codes</option>
                                                            <option
                                                                v-for="code in availableCodePrefixes"
                                                                :key="code"
                                                                :value="code"
                                                            >
                                                                {{ code }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <!-- Active Filters Badges -->
                                                <div class="row mt-2" v-if="hasActiveFilters">
                                                    <div class="col-12">
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <span
                                                                v-for="(value, key) in activeFilters"
                                                                :key="key"
                                                                class="badge badge-info"
                                                            >
                                                                {{ getFilterLabel(key, value) }}
                                                                <button
                                                                    @click="removeFilter(key)"
                                                                    class="badge badge-light ml-1 p-0 border-0"
                                                                    style="background: transparent;"
                                                                >
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                                </transition>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <br>

                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="align-top">ID</th>
                                                <sortable-th label="Name" sort-key="name" :current-sort="sortState" @sort="onSort" />
                                                <sortable-th label="Code" sort-key="code" :current-sort="sortState" @sort="onSort" />
                                                <th class="align-top">Colour</th>
                                                <sortable-th label="Fee (RM)" sort-key="fee" :current-sort="sortState" @sort="onSort" />
                                                <th class="align-top">Description</th>
                                                <th class="align-top">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for='(data,index) in filteredServes' :key="data.id" >
                                                <td>{{index+1}}</td>
                                                <td>
                                                    <div class="font-weight-bold">{{data.name}}</div>
                                                    <small class="text-muted">{{data.code}}</small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-secondary">{{data.code}}</span>
                                                </td>
                                                <td>
                                                    <div class="serve-options">
                                                        <label class="serve-item">
                                                            <span
                                                                class="serve-badge"
                                                                :style="{
                                                                    backgroundColor: data.colour,
                                                                    color: getTextColor(data.colour),
                                                                    border: isLightColor(data.colour) ? '1px solid #dee2e6' : 'none'
                                                                }"
                                                            >
                                                                {{ data.colour }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span :class="data.fee == 0 ? 'badge badge-success' : 'badge badge-primary'">
                                                        RM {{ parseFloat(data.fee).toFixed(2) }}
                                                    </span>
                                                </td>
                                                <td style="text-align: left;">
                                                    <div v-if="data.description">
                                                        <div v-if="data.description.length > 100">
                                                            <span v-html="formatDescription(data.description.substring(0, 100))"></span>...
                                                            <button
                                                                @click="toggleDescription(data.id)"
                                                                class="btn btn-link btn-sm p-0 ml-1"
                                                            >
                                                                {{ expandedDescriptions.includes(data.id) ? 'Show Less' : 'Read More' }}
                                                            </button>
                                                            <div v-if="expandedDescriptions.includes(data.id)" class="mt-1">
                                                                <span v-html="formatDescription(data.description.substring(100))"></span>
                                                            </div>
                                                        </div>
                                                        <div v-else>
                                                            <span v-html="formatDescription(data.description)"></span>
                                                        </div>
                                                    </div>
                                                    <span v-else class="text-muted">No description</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <router-link
                                                            :to="{name:'Serveedit', params:{id:data.id}}"
                                                            class="btn btn-sm btn-primary mr-1"
                                                            title="Edit"
                                                        >
                                                            <i class="fas fa-edit"></i>
                                                        </router-link>
                                                        <a
                                                            href='javascript:void(0)'
                                                            @click='deleteServe(data.id)'
                                                            class="btn btn-sm btn-danger"
                                                            title="Delete"
                                                        >
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="filteredServes.length === 0">
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    No services found.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="text-center">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import ColumnSearchPanel from '../shared/ColumnSearchPanel.vue';
    import SortableTh from '../shared/SortableTh.vue';

    export default {
        components: { ColumnSearchPanel, SortableTh },
        data() {
            return {
                serves: [],
                showFilters: false,
                filters: {
                    search: '',
                    feeRange: '',
                    color: '',
                    codeStartsWith: ''
                },
                sortState: { key: 'name', dir: 'asc' },
                expandedDescriptions: [],
                availableColors: [],
                availableCodePrefixes: []
            }
        },
        methods: {
            // Renders the small subset of Markdown actually used in
            // serves.description (### headers, **bold**, `code`, * bullet
            // lists) -- no markdown library is installed in this project, and
            // this admin-authored business text (eligibility rules, ID
            // formats, perks) never needs anything beyond these four
            // constructs. Escapes HTML first since the result is bound via
            // v-html.
            formatDescription(text) {
                if (!text) return '';

                const escapeHtml = (str) => str.replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const lines = escapeHtml(text).split('\n');
                let html = '';
                let inList = false;

                const closeList = () => {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                };

                const inline = (line) => line
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/`(.+?)`/g, '<code>$1</code>');

                lines.forEach(line => {
                    const heading = line.match(/^(#{1,6})\s+(.*)$/);
                    const listItem = line.match(/^[*-]\s+(.*)$/);

                    if (heading) {
                        closeList();
                        const level = Math.min(heading[1].length + 3, 6);
                        html += `<h${level} class="description-heading">${inline(heading[2])}</h${level}>`;
                    } else if (listItem) {
                        if (!inList) {
                            html += '<ul class="description-list">';
                            inList = true;
                        }
                        html += `<li>${inline(listItem[1])}</li>`;
                    } else if (line.trim() === '') {
                        closeList();
                    } else {
                        closeList();
                        html += `<div>${inline(line)}</div>`;
                    }
                });

                closeList();
                return html;
            },
            getTextColor(bgColor) {
                if (!bgColor) return '#000';
                // Convert hex to RGB
                let r, g, b;
                if (bgColor.startsWith('#')) {
                    const hex = bgColor.replace('#', '');
                    r = parseInt(hex.substr(0, 2), 16);
                    g = parseInt(hex.substr(2, 2), 16);
                    b = parseInt(hex.substr(4, 2), 16);
                } else if (bgColor.startsWith('rgb')) {
                    const rgb = bgColor.match(/\d+/g);
                    r = parseInt(rgb[0]);
                    g = parseInt(rgb[1]);
                    b = parseInt(rgb[2]);
                } else {
                    // Try to convert named color
                    const temp = document.createElement('div');
                    temp.style.color = bgColor;
                    document.body.appendChild(temp);
                    const rgb = window.getComputedStyle(temp).color;
                    document.body.removeChild(temp);
                    const match = rgb.match(/\d+/g);
                    if (match) {
                        r = parseInt(match[0]);
                        g = parseInt(match[1]);
                        b = parseInt(match[2]);
                    } else {
                        return '#000';
                    }
                }

                // Calculate brightness
                const brightness = (r * 299 + g * 587 + b * 114) / 1000;
                return brightness > 160 ? '#000' : '#fff';
            },
            getEmp(){
                axios.get('/api/serves')
                .then(res => {
                    this.serves = res.data;
                    this.extractFilterOptions();
                })
                .catch(err => {
                    console.error('Error fetching serves:', err);
                })
            },
            deleteServe(id){
                Swal.fire({
                    title: 'Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.value) {
                        axios.delete("/api/serves/"+id)
                        .then(() => {
                            this.serves = this.serves.filter(data => data.id !== id);
                            this.extractFilterOptions();
                        })
                        .catch(() => {
                            this.$router.push({ name:'serves'})
                        })
                        Swal.fire(
                            'Deleted!',
                            'Your file has been deleted.',
                            'success'
                        )
                    }
                })
            },
            isLightColor(hex) {
                if (!hex) return false;

                if (hex.startsWith('#')) {
                    hex = hex.replace('#', '');
                    const r = parseInt(hex.substr(0, 2), 16);
                    const g = parseInt(hex.substr(2, 2), 16);
                    const b = parseInt(hex.substr(4, 2), 16);
                    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
                    return brightness > 180;
                }
                return false;
            },
            extractFilterOptions() {
                // Extract unique colors
                const colors = new Set();
                // Extract code prefixes (first character of code)
                const codePrefixes = new Set();

                this.serves.forEach(serve => {
                    if (serve.colour) {
                        colors.add(serve.colour);
                    }
                    if (serve.code && serve.code.length > 0) {
                        codePrefixes.add(serve.code.charAt(0).toUpperCase());
                    }
                });

                this.availableColors = Array.from(colors).sort();
                this.availableCodePrefixes = Array.from(codePrefixes).sort();
            },
            applyFilters() {
                // Filters are applied automatically through computed property
            },
            clearFilters() {
                this.filters = {
                    search: '',
                    feeRange: '',
                    color: '',
                    codeStartsWith: ''
                };
            },
            removeFilter(filterKey) {
                if (this.filters[filterKey] !== undefined) {
                    this.filters[filterKey] = '';
                }
            },
            getFilterLabel(key, value) {
                const labels = {
                    feeRange: {
                        'free': 'Free',
                        'low': 'Low Fee',
                        'medium': 'Medium Fee',
                        'high': 'High Fee'
                    }
                };

                if (key === 'search') {
                    return `Search: ${value}`;
                }

                if (key === 'color') {
                    return `Color: ${value}`;
                }

                if (key === 'codeStartsWith') {
                    return `Code starts with: ${value}`;
                }

                return labels[key] && labels[key][value]
                    ? `${key.replace(/([A-Z])/g, ' $1').toUpperCase()}: ${labels[key][value]}`
                    : `${key}: ${value}`;
            },
            toggleDescription(serveId) {
                const index = this.expandedDescriptions.indexOf(serveId);
                if (index > -1) {
                    this.expandedDescriptions.splice(index, 1);
                } else {
                    this.expandedDescriptions.push(serveId);
                }
            },
            sortServes(serves) {
                const dir = this.sortState.dir === 'desc' ? -1 : 1;
                const sorted = [...serves];
                switch (this.sortState.key) {
                    case 'fee':
                        return sorted.sort((a, b) => dir * (parseFloat(a.fee) - parseFloat(b.fee)));
                    case 'code':
                        return sorted.sort((a, b) => dir * (a.code || '').localeCompare(b.code || ''));
                    case 'name':
                    default:
                        return sorted.sort((a, b) => dir * (a.name || '').localeCompare(b.name || ''));
                }
            },
            onSort(key) {
                if (this.sortState.key === key) {
                    this.sortState.dir = this.sortState.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortState.key = key;
                    this.sortState.dir = 'asc';
                }
            }
        },
        computed: {
            filterColumns() {
                return [
                    { key: 'search', label: 'Name / Code / Description', type: 'text' },
                    { key: 'feeRange', label: 'Fee Range', type: 'select', options: [
                        { value: 'free', label: 'Free (RM 0)' },
                        { value: 'low', label: 'Low (RM 1 - 100)' },
                        { value: 'medium', label: 'Medium (RM 101 - 500)' },
                        { value: 'high', label: 'High (RM 501+)' },
                    ] },
                    { key: 'color', label: 'Color', type: 'select', options: this.availableColors.map(c => ({ value: c, label: c })) },
                ];
            },
            filteredServes() {
                let filtered = this.serves;

                // Apply text search
                if (this.filters.search) {
                    const keyword = this.filters.search.toLowerCase();
                    filtered = filtered.filter(data =>
                        (data.name && data.name.toLowerCase().includes(keyword)) ||
                        (data.code && data.code.toLowerCase().includes(keyword)) ||
                        (data.description && data.description.toLowerCase().includes(keyword))
                    );
                }

                // Apply fee range filter
                if (this.filters.feeRange) {
                    filtered = filtered.filter(data => {
                        const fee = parseFloat(data.fee) || 0;
                        switch (this.filters.feeRange) {
                            case 'free': return fee === 0;
                            case 'low': return fee > 0 && fee <= 100;
                            case 'medium': return fee > 100 && fee <= 500;
                            case 'high': return fee > 500;
                            default: return true;
                        }
                    });
                }

                // Apply color filter
                if (this.filters.color) {
                    filtered = filtered.filter(data => data.colour === this.filters.color);
                }

                // Apply code starts with filter
                if (this.filters.codeStartsWith) {
                    filtered = filtered.filter(data =>
                        data.code &&
                        data.code.charAt(0).toUpperCase() === this.filters.codeStartsWith
                    );
                }

                // Apply sorting
                filtered = this.sortServes(filtered);

                return filtered;
            },
            hasActiveFilters() {
                return Object.entries(this.filters).some(([key, value]) => value !== '');
            },
            activeFilters() {
                const active = {};
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '') {
                        active[key] = value;
                    }
                });
                return active;
            }
        },
        created() {
            if (!User.loggedIn()) {
                this.$router.push({
                    name: 'login'
                })
            };
            this.getEmp();
        },
    }
</script>

<style scoped>
    .table th, .table td {
        vertical-align: middle !important;
    }

    .description-heading {
        font-size: 0.85rem;
        font-weight: 700;
        margin: 0.5rem 0 0.15rem;
        color: #4e73df;
    }

    .description-heading:first-child {
        margin-top: 0;
    }

    .description-list {
        margin: 0 0 0.25rem;
        padding-left: 1.1rem;
    }

    .description-list li {
        font-size: 0.85rem;
    }

    .serve-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }

    .serve-item {
        display: flex;
        align-items: left;
        gap: 10px;
        cursor: pointer;
    }

    .serve-badge {
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: 500;
        white-space: nowrap;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: all 0.15s ease;
        min-width: 80px;
        text-align: center;
    }

    .serve-item:hover .serve-badge {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
    }

    /* Active Filter Badges */
    .badge-info {
        background-color: #36b9cc !important;
        font-size: 0.75em;
        padding: 0.4em 0.8em;
    }

    /* Gap utility for badges */
    .gap-2 {
        gap: 0.5rem;
    }

    /* Read More button */
    .btn-link {
        text-decoration: none;
        font-size: 0.8em;
        color: #007bff;
    }

    /* Color filter dropdown options */
    select option {
        padding: 8px !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .table-responsive {
            font-size: 0.8rem;
        }

        .col-md-3 {
            margin-bottom: 10px;
        }

        .col-xl-3 {
            margin-bottom: 15px;
        }

        .serve-badge {
            padding: 6px 10px;
            font-size: 0.8em;
        }
    }

    .filter-panel-enter-active,
    .filter-panel-leave-active {
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .filter-panel-enter,
    .filter-panel-leave-to {
        opacity: 0;
        transform: translateY(-8px);
    }
</style>
