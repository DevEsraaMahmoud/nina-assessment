<script setup>
import Modal from '@/Components/Modal.vue';
import DeleteConfirmationModal from '@/Components/DeleteConfirmationModal.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    userId: {
        type: Number,
        default: null,
    },
    user: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'edit', 'deleted']);
const userData = ref(props.user);
const showDeleteConfirmation = ref(false);
const isLoading = ref(false);
const loadError = ref(false);

// Fetch user with address when modal opens
watch(
    () => [props.show, props.userId],
    ([show, userId]) => {
        if (show && userId) {
            // Use basic user data from props immediately
            userData.value = props.user;
            
            // Fetch full user details with address via API
            isLoading.value = true;
            loadError.value = false;
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch(route('users.show', userId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.user) {
                        userData.value = data.user;
                    }
                    isLoading.value = false;
                })
                .catch(error => {
                    console.error('Error loading user details:', error);
                    loadError.value = true;
                    isLoading.value = false;
                });
        } else if (!show) {
            // Reset when modal closes
            userData.value = null;
            showDeleteConfirmation.value = false;
            loadError.value = false;
            isLoading.value = false;
        }
    },
    { immediate: true }
);

const close = () => {
    emit('close');
};

const openDeleteConfirmation = () => {
    showDeleteConfirmation.value = true;
};

const closeDeleteConfirmation = () => {
    showDeleteConfirmation.value = false;
};

const confirmDelete = () => {
    router.delete(route('users.destroy', props.userId), {
        preserveScroll: false,
        onSuccess: (page) => {
            closeDeleteConfirmation();
            close();
            // Emit delete event to parent to trigger reload
            emit('deleted');
            // Reload users and notifications
            router.reload({ 
                only: ['users', 'notifications'], 
                preserveState: true,
                preserveScroll: false 
            });
        },
        onError: () => {
            closeDeleteConfirmation();
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between pb-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-600 to-indigo-700 text-white">
                        <i class="fas fa-user text-lg"></i>
                    </div>
                    User Details
                </h2>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-all duration-200"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="py-12 text-center">
                <div class="flex flex-col items-center justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
                    <p class="text-sm text-gray-600 font-medium">Loading user details...</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="loadError && !userData" class="py-12 text-center">
                <div class="flex flex-col items-center justify-center">
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 text-red-600 mb-4">
                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-red-600">Failed to load user details.</p>
                    <p class="text-xs text-gray-500 mt-1">Please try again later</p>
                </div>
            </div>

            <!-- User Details -->
            <div v-else-if="userData" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- User Information -->
                    <div>
                        <h3 class="mb-6 text-lg font-bold text-gray-900 flex items-center gap-2">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-indigo-700 text-white">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            User Information
                        </h3>
                        <dl class="space-y-5">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-user text-gray-400"></i>
                                    First Name
                                </dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">
                                    {{ userData.first_name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-user text-gray-400"></i>
                                    Last Name
                                </dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">
                                    {{ userData.last_name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                    Email
                                </dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">
                                    {{ userData.email }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Address Information -->
                    <div>
                        <h3 class="mb-6 text-lg font-bold text-gray-900 flex items-center gap-2">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-indigo-700 text-white">
                                <i class="fas fa-map-marker-alt text-sm"></i>
                            </div>
                            Address Information
                        </h3>
                        <dl
                            v-if="userData.address"
                            class="space-y-5"
                        >
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-road text-gray-400"></i>
                                    Street
                                </dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">
                                    {{ userData.address.street }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-city text-gray-400"></i>
                                    City
                                </dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">
                                    {{ userData.address.city }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-mail-bulk text-gray-400"></i>
                                    Post Code
                                </dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">
                                    {{ userData.address.post_code }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-flag text-gray-400"></i>
                                    Country
                                </dt>
                                <dd class="mt-2 text-base font-medium text-gray-900">
                                    {{ userData.address.country }}
                                </dd>
                            </div>
                        </dl>
                        <div
                            v-else
                            class="py-8 text-center rounded-lg bg-gray-50 border border-gray-200"
                        >
                            <i class="fas fa-map-marker-alt text-3xl text-gray-300 mb-3"></i>
                            <p class="text-sm font-medium text-gray-500">No address information available</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 border-t border-gray-200/50 pt-6">
                    <button
                        @click="emit('edit', userData); close();"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:shadow-indigo-600/40 hover:from-indigo-700 hover:to-indigo-800 transition-all duration-200"
                    >
                        <i class="fas fa-edit"></i>
                        Edit User
                    </button>
                    <button
                        @click="openDeleteConfirmation"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/30 hover:shadow-red-600/40 hover:from-red-700 hover:to-red-800 transition-all duration-200"
                    >
                        <i class="fas fa-trash"></i>
                        Delete User
                    </button>

                    <button
                        @click="close"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
                    >
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :show="showDeleteConfirmation"
            title="Delete User"
            :message="`Are you sure you want to delete ${userData?.first_name} ${userData?.last_name}? This action cannot be undone.`"
            confirm-text="Delete User"
            @close="closeDeleteConfirmation"
            @confirm="confirmDelete"
        />
    </Modal>
</template>

