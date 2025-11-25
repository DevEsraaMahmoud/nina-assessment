<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserDetailsModal from '@/Components/UserDetailsModal.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

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
const selectedUserId = ref(null);
const selectedUser = ref(null);
const toasts = ref([]);
const isSearching = ref(false);

// Show toast from flash message
const showFlashMessages = () => {
    if (page.props.flash?.success) {
        addToast(page.props.flash.success, 'success');
    }
    if (page.props.flash?.error) {
        addToast(page.props.flash.error, 'error');
    }
};

onMounted(() => {
    showFlashMessages();
});

watch(
    () => page.props.flash,
    () => {
        showFlashMessages();
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
        searchTimeout = setTimeout(executeSearch, 1000);
    }
};

const handleSearchEnter = (event) => {
    if (event.key === 'Enter' && !isSearching.value) {
        performSearch(searchQuery.value, true);
    }
};

watch(searchQuery, (newValue) => {
    performSearch(newValue);
});

const openUserModal = (user) => {
    selectedUserId.value = user.id;
    selectedUser.value = user;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedUserId.value = null;
    selectedUser.value = null;
};
</script>

<template>
    <Head title="Users Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Users Dashboard
                </h2>
                <div class="flex items-center gap-4">
                    <NotificationBell :notifications="notifications" />
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="border-b border-gray-200 bg-white px-6 py-4">
                        <div class="flex items-center">
                            <div class="relative w-full max-w-sm">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by name or email..."
                                    :disabled="isSearching"
                                    :readonly="isSearching"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm md:text-base py-2.5 px-4 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    @keyup.enter="handleSearchEnter"
                                />
                                <div
                                    v-if="isSearching"
                                    class="absolute right-3 top-1/2 -translate-y-1/2"
                                >
                                    <svg
                                        class="h-5 w-5 animate-spin text-indigo-600"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 ml-auto">
                    <Link
                        :href="route('users.create')"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        Add New User
                    </Link>
                </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Name
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Email
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Address
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        <button
                                            @click="openUserModal(user)"
                                            class="text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                                        >
                                            {{ user.first_name }} {{ user.last_name }}
                                        </button>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ user.email }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div v-if="user.address">
                                            {{ user.address.street }}, {{ user.address.city }}, {{ user.address.post_code }}, {{ user.address.country }}
                                        </div>
                                        <span v-else class="text-gray-400">No address</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button
                                            @click="openUserModal(user)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            View
                                        </button>
                                        <Link
                                            :href="route('users.edit', user.id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-6 py-4 text-center text-sm text-gray-500"
                                    >
                                        No users found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="users.links && users.links.length > 3"
                        class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <Link
                                    v-if="users.links[0].url"
                                    :href="users.links[0].url"
                                    class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="users.links[users.links.length - 1].url"
                                    :href="users.links[users.links.length - 1].url"
                                    class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing
                                        <span class="font-medium">{{ users.from }}</span>
                                        to
                                        <span class="font-medium">{{ users.to }}</span>
                                        of
                                        <span class="font-medium">{{ users.total }}</span>
                                        results
                                    </p>
                                </div>
                                <div>
                                    <nav
                                        class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                                        aria-label="Pagination"
                                    >
                                        <template
                                            v-for="(link, index) in users.links"
                                            :key="index"
                                        >
                                            <Link
                                                v-if="link.url"
                                                :href="link.url"
                                                v-html="link.label"
                                                :class="[
                                                    'relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                                                    link.active
                                                        ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                                        : 'text-gray-900',
                                                ]"
                                            />
                                            <span
                                                v-else
                                                v-html="link.label"
                                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-400 ring-1 ring-inset ring-gray-300"
                                            />
                                        </template>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <UserDetailsModal
            :show="showModal"
            :user-id="selectedUserId"
            :user="selectedUser"
            @close="closeModal"
        />

        <ToastContainer :toasts="toasts" @remove="removeToast" />
    </AuthenticatedLayout>
</template>
