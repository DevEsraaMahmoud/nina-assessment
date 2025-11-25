<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserDetailsModal from '@/Components/UserDetailsModal.vue';
import UserFormModal from '@/Components/UserFormModal.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch, onMounted, computed } from 'vue';

const props = defineProps({
    users: Object,
    search: String,
    notifications: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const searchQuery = ref(props.search || '');
const showModal = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedUserId = ref(null);
const selectedUser = ref(null);
const editUser = ref(null);
const toasts = ref([]);
const isSearching = ref(false);
const isLoadingUser = ref(false);

// Track shown flash messages to prevent duplicates
const shownFlashMessages = ref(new Set());

// Show toast from flash message (only once per message)
const showFlashMessages = () => {
    const flash = page.props.flash;
    if (!flash) return;
    
    if (flash.success) {
        const messageKey = `success-${flash.success}`;
        if (!shownFlashMessages.value.has(messageKey)) {
            addToast(flash.success, 'success');
            shownFlashMessages.value.add(messageKey);
            // Clear after a delay to allow same message again if needed
            setTimeout(() => {
                shownFlashMessages.value.delete(messageKey);
            }, 1000);
        }
    }
    if (flash.error) {
        const messageKey = `error-${flash.error}`;
        if (!shownFlashMessages.value.has(messageKey)) {
            addToast(flash.error, 'error');
            shownFlashMessages.value.add(messageKey);
            setTimeout(() => {
                shownFlashMessages.value.delete(messageKey);
            }, 1000);
        }
    }
};

onMounted(() => {
    showFlashMessages();
});

watch(
    () => page.props.flash,
    (newFlash, oldFlash) => {
        // Only show if flash actually changed
        if (newFlash && newFlash !== oldFlash) {
        showFlashMessages();
        }
    },
    { deep: true }
);

const addToast = (message, type = 'success') => {
    const toast = {
        message,
        type,
        duration: 3000,
    };
    toasts.value.push(toast);
};

const removeToast = (index) => {
    toasts.value.splice(index, 1);
};

let searchTimeout = null;
const performSearch = (query, immediate = false) => {
    if (isSearching.value) {
        return;
    }
    
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    const executeSearch = () => {
        if (isSearching.value) {
            return;
        }
        
        isSearching.value = true;
        
        router.get(
            route('users.index'),
            { search: query },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => {
                    isSearching.value = false;
                },
                onError: () => {
                    isSearching.value = false;
                },
            }
        );
    };
    
    if (immediate) {
        executeSearch();
    } else {
        // more responsive debounce for UX (1s is ok; you can lower to 300ms)
        searchTimeout = setTimeout(executeSearch, 800);
    }
};

const handleSearchEnter = (event) => {
    if (event.key === 'Enter' && !isSearching.value) {
        performSearch(searchQuery.value, true);
    }
};

watch(searchQuery, (newValue, oldValue) => {
    // do nothing if same
    if (newValue === oldValue) return;
    performSearch(newValue);
});

// Open modal and fetch user details from API
const openUserModal = async (user) => {
    selectedUserId.value = user.id;
    selectedUser.value = null;
    showModal.value = true;
    isLoadingUser.value = true;

    try {
        // Expect a JSON endpoint that returns full user object with address:
        // GET /users/{id} => { user: { id, first_name, last_name, email, address: {...} } }
        const res = await axios.get(route('users.show', user.id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });

        // Some setups return res.data.user or res.data; adapt as needed:
        selectedUser.value = res.data.user ?? res.data;
    } catch (e) {
        addToast('Failed to load user details', 'error');
        // fallback to minimal data
        selectedUser.value = user;
    } finally {
        isLoadingUser.value = false;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedUserId.value = null;
    selectedUser.value = null;
};

const handleUserDeleted = () => {
    // Reload the users table after deletion
    router.reload({ 
        only: ['users', 'notifications'], 
        preserveState: true, 
        preserveScroll: true 
    });
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const openEditModal = async (user) => {
    editUser.value = null;
    showEditModal.value = true;
    
    // Fetch full user data with address
    try {
        const res = await axios.get(route('users.show', user.id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        editUser.value = res.data.user ?? res.data;
    } catch (e) {
        // Fallback to basic user data
        editUser.value = user;
    }
};

const closeEditModal = () => {
    showEditModal.value = false;
    editUser.value = null;
};

// Watch for successful form submissions to reload data
watch([showCreateModal, showEditModal], ([createOpen, editOpen], [prevCreateOpen, prevEditOpen]) => {
    // Only reload if modals were open and are now closed (indicating successful submission)
    if ((prevCreateOpen && !createOpen) || (prevEditOpen && !editOpen)) {
        // Reload users when modals close (form submission handled by Inertia)
        router.reload({ only: ['users', 'notifications'], preserveState: true, preserveScroll: true });
    }
});

// Smart pagination: only show relevant page numbers
const getVisiblePageLinks = computed(() => {
    if (!props.users?.links || props.users.links.length <= 3) {
        return [];
    }

    const currentPage = props.users.current_page || 1;
    const lastPage = props.users.last_page || 1;
    
    if (lastPage <= 1) return [];
    
    const visibleLinks = [];
    const windowSize = 5; // Show 5 pages around current page
    
    // Calculate start and end page
    let startPage = Math.max(1, currentPage - Math.floor(windowSize / 2));
    let endPage = Math.min(lastPage, startPage + windowSize - 1);
    
    // Adjust if we're near the end
    if (endPage - startPage < windowSize - 1) {
        startPage = Math.max(1, endPage - windowSize + 1);
    }

    // Helper to build page URL using Laravel paginator's URL structure
    const buildPageUrl = (page) => {
        // Use the existing links structure to get proper URLs with query params
        const existingLink = props.users.links.find(link => {
            if (!link.url) return false;
            const url = new URL(link.url, window.location.origin);
            const pageParam = url.searchParams.get('page');
            return pageParam && parseInt(pageParam) === page;
        });
        
        if (existingLink && existingLink.url) {
            return existingLink.url;
        }
        
        // Fallback: build URL manually
        const baseUrl = props.users.path || window.location.pathname;
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);
        if (props.search) {
            params.set('search', props.search);
        }
        return baseUrl + '?' + params.toString();
    };

    // Always show first page if not in current window
    if (startPage > 1) {
        visibleLinks.push({
            label: '1',
            url: buildPageUrl(1),
            active: currentPage === 1
        });
        
        if (startPage > 2) {
            visibleLinks.push({ label: '...', url: null, active: false });
        }
    }

    // Add pages in current window
    for (let i = startPage; i <= endPage; i++) {
        visibleLinks.push({
            label: String(i),
            url: buildPageUrl(i),
            active: i === currentPage
        });
    }

    // Always show last page if not in current window
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            visibleLinks.push({ label: '...', url: null, active: false });
        }
        visibleLinks.push({
            label: String(lastPage),
            url: buildPageUrl(lastPage),
            active: currentPage === lastPage
        });
    }

    return visibleLinks;
});
</script>

<template>
    <Head title="Users Dashboard" />

    <AuthenticatedLayout>
        
        <template #right>
                    <NotificationBell :notifications="notifications" />
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white/80 backdrop-blur-sm shadow-xl shadow-gray-200/50 sm:rounded-2xl overflow-hidden border border-gray-200/50">

                    <!-- Search + Actions -->
                    <div class="px-6 py-5 border-b border-gray-200/50 bg-gradient-to-r from-gray-50/50 to-white">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                            <div class="relative w-full sm:max-w-xl">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name or email..."
                                    :disabled="isSearching"
                                    :readonly="isSearching"
                                    @keyup.enter="handleSearchEnter"
                                    class="block w-full rounded-xl border border-gray-200 bg-white py-3 pl-11 pr-12 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                />
                                <div v-if="isSearching" class="absolute right-4 top-1/2 -translate-y-1/2">
                                    <i class="fas fa-spinner fa-spin text-indigo-600"></i>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 sm:ml-auto">
                                <button
                                    @click="openCreateModal"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:shadow-indigo-600/40 hover:from-indigo-700 hover:to-indigo-800 transition-all duration-200"
                    >
                                    <i class="fas fa-plus"></i>
                        Add New User
                                </button>
                </div>
                        </div>
                    </div>

                    <!-- Users table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">
                                        <span class="flex items-center gap-2"><i class="fas fa-user text-indigo-500"></i> Name</span>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-700">
                                        <span class="flex items-center gap-2"><i class="fas fa-envelope text-indigo-500"></i> Email</span>
                                    </th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-700">
                                        <span class="flex items-center justify-end gap-2"><i class="fas fa-cog text-indigo-500"></i> Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200/50">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-indigo-50/30 transition-all duration-150 group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            @click="openUserModal(user)"
                                            class="flex items-center gap-3 text-gray-900 hover:text-indigo-600 transition-colors group-hover:translate-x-1 duration-150"
                                        >
                                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-100 to-indigo-200 text-indigo-700 group-hover:from-indigo-200 group-hover:to-indigo-300 transition-all">
                                                <i class="fas fa-user text-sm"></i>
                                            </div>
                                            <span class="font-medium">{{ user.first_name }} {{ user.last_name }}</span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-envelope text-gray-400"></i>
                                        {{ user.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openEditModal(user)"
                                            class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-3 py-1.5 rounded-lg transition-all duration-150"
                                        >
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="users.data.length === 0">
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                                                <i class="fas fa-users text-2xl"></i>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900">No users found</p>
                                            <p class="text-sm text-gray-500 mt-1">Try adjusting your search criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.links && users.links.length > 3" class="border-t border-gray-200/50 bg-gradient-to-r from-gray-50/50 to-white px-6 py-4">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-sm text-gray-600">
                                Showing <span class="font-semibold text-gray-900">{{ users.from }}</span> to <span class="font-semibold text-gray-900">{{ users.to }}</span> of <span class="font-semibold text-gray-900">{{ users.total }}</span> results
                            </div>
                            <nav class="flex items-center gap-2" aria-label="Pagination">
                                <!-- Previous Page -->
                                <Link
                                    v-if="users.links[0]?.url"
                                    :href="users.links[0].url"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-150"
                                    title="Previous page"
                                >
                                    <i class="fas fa-chevron-left text-xs mr-1"></i>
                                    <span class="hidden sm:inline">Previous</span>
                                </Link>
                                <span 
                                    v-else
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed"
                                >
                                    <i class="fas fa-chevron-left text-xs mr-1"></i>
                                    <span class="hidden sm:inline">Previous</span>
                                </span>

                                <!-- Page Numbers (smart display) -->
                                <template v-for="(link, index) in getVisiblePageLinks" :key="index">
                                    <Link 
                                        v-if="link.url && link.label !== '...'"
                                        :href="link.url"
                                        :class="[
                                            'inline-flex items-center justify-center min-w-[2.5rem] px-3 py-2 text-sm font-medium rounded-lg transition-all duration-150',
                                            link.active 
                                                ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md' 
                                                : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                                        ]"
                                    >
                                        {{ link.label }}
                                    </Link>
                                    <span 
                                        v-else-if="link.label === '...'"
                                        class="inline-flex items-center justify-center px-2 py-2 text-sm font-medium text-gray-500"
                                    >
                                        ...
                                    </span>
                                </template>

                                <!-- Next Page -->
                                <Link
                                    v-if="users.links[users.links.length - 1]?.url"
                                    :href="users.links[users.links.length - 1].url"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-150"
                                    title="Next page"
                                >
                                    <span class="hidden sm:inline">Next</span>
                                    <i class="fas fa-chevron-right text-xs ml-1"></i>
                                </Link>
                                            <span
                                                v-else
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed"
                                >
                                    <span class="hidden sm:inline">Next</span>
                                    <i class="fas fa-chevron-right text-xs ml-1"></i>
                                </span>
                                    </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <UserDetailsModal
            :show="showModal"
            :user-id="selectedUserId"
            :user="selectedUser"
            :loading="isLoadingUser"
            @close="closeModal"
            @edit="openEditModal"
            @deleted="handleUserDeleted"
        />

        <UserFormModal
            :show="showCreateModal"
            :user="null"
            @close="closeCreateModal"
        />

        <UserFormModal
            :show="showEditModal"
            :user="editUser"
            @close="closeEditModal"
        />

        <ToastContainer :toasts="toasts" @remove="removeToast" />
    </AuthenticatedLayout>
</template>

<style scoped>
/* Elegant table styling */
table {
    border-collapse: separate;
    border-spacing: 0;
}

/* Smooth transitions for table rows */
tbody tr {
    transition: all 0.15s ease-in-out;
}

/* Icon consistency */
.fas, .far {
    display: inline-block;
    width: 1em;
    text-align: center;
}
</style>
