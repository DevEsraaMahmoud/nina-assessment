<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed, nextTick } from 'vue';
import countries from '@/data/countries.json';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const isEditMode = computed(() => !!props.user);

// Selected country ref (stores country code)
const selectedCountry = ref(null);

// Helper to get country code from country name
const getCountryCode = (countryName) => {
    const country = countries.find(x => x.name === countryName);
    return country ? country.code : null;
};

// Helper to get country name from country code
const getCountryName = (countryCode) => {
    const country = countries.find(x => x.code === countryCode);
    return country ? country.name : countryCode;
};

// Computed property for countries list - include user's country if not in list (for edit mode)
const countryOptions = computed(() => {
    const countryList = countries.map(c => ({ code: c.code, name: c.name }));
    
    // If in edit mode and user has a country not in our list, add it
    if (isEditMode.value && props.user?.address?.country) {
        const userCountryName = props.user.address.country;
        const existsInList = countries.find(c => c.name === userCountryName);
        if (!existsInList) {
            return [{ code: userCountryName, name: userCountryName }, ...countryList];
        }
    }
    
    return countryList;
});

// Computed property for cities based on selected country
const cityOptions = computed(() => {
    if (!selectedCountry.value) {
        return [];
    }
    
    // Find country by code
    const c = countries.find(x => x.code === selectedCountry.value);
    
    if (!c) {
        // If country not found but we're in edit mode, allow the user's existing city
        if (isEditMode.value && props.user?.address?.city && 
            getCountryName(selectedCountry.value) === props.user.address.country) {
            return [props.user.address.city];
        }
        return [];
    }
    
    const cities = [...c.cities];
    
    // If in edit mode and user has a city that's not in the list, add it
    if (isEditMode.value && props.user?.address?.city && 
        getCountryName(selectedCountry.value) === props.user.address.country &&
        !cities.includes(props.user.address.city)) {
        cities.unshift(props.user.address.city);
    }
    
    return cities;
});

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    address: {
        country: '',
        city: '',
        post_code: '',
        street: '',
    },
});

// Track if we're initializing the form to prevent city reset
const isInitializing = ref(false);

// Watch for user prop changes to populate form for edit mode
watch(
    () => props.user,
    async (user) => {
        isInitializing.value = true;
        if (user) {
            form.first_name = user.first_name || '';
            form.last_name = user.last_name || '';
            form.email = user.email || '';
            form.address.post_code = user.address?.post_code || '';
            form.address.street = user.address?.street || '';
            
            // Set country first, then wait for computed properties to update
            const userCountry = user.address?.country || '';
            const userCity = user.address?.city || '';
            
            if (userCountry) {
                form.address.country = userCountry;
                selectedCountry.value = getCountryCode(userCountry) || userCountry;
                // Wait for cityOptions computed property to update
                await nextTick();
                // Now set the city - it should be in cityOptions by now
                if (userCity) {
                    form.address.city = userCity;
                }
            }
            
            // Wait another tick to ensure everything is properly set
            await nextTick();
            isInitializing.value = false;
        } else {
            // Reset form for create mode
            form.reset();
            form.address = {
                country: '',
                city: '',
                post_code: '',
                street: '',
            };
            selectedCountry.value = null;
            isInitializing.value = false;
        }
    },
    { immediate: true }
);

// Watch country changes to reset city (but not during initial load)
watch(
    () => form.address.country,
    (newCountry, oldCountry) => {
        // Update selectedCountry ref when form country changes (convert name to code)
        selectedCountry.value = getCountryCode(newCountry) || newCountry;
        
        // Only reset city if country actually changed and we're not initializing
        if (!isInitializing.value && newCountry !== oldCountry && oldCountry !== '' && oldCountry !== null && oldCountry !== undefined) {
            // Reset city when country changes
            form.address.city = '';
            // Clear city validation errors
            if (form.errors['address.city']) {
                form.clearErrors('address.city');
            }
        }
    }
);

// Watch selectedCountry to update form (convert code to name for database)
watch(selectedCountry, (newCountryCode) => {
    if (!isInitializing.value) {
        const countryName = getCountryName(newCountryCode);
        if (form.address.country !== countryName) {
            form.address.country = countryName || '';
        }
    }
});

// Reset form when modal closes
watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
        }
    }
);

const submit = () => {
    if (isEditMode.value) {
        form.put(route('users.update', props.user.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
        });
    } else {
        form.post(route('users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('close');
            },
        });
    }
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" max-width="2xl">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between pb-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-600 to-indigo-700 text-white">
                        <i :class="isEditMode ? 'fas fa-user-edit' : 'fas fa-user-plus'" class="text-lg"></i>
                    </div>
                    {{ isEditMode ? 'Edit User' : 'Create New User' }}
                </h2>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-2 transition-all duration-200"
                >
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-6">
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
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2 mb-2">
                                    <i class="fas fa-user text-gray-400"></i>
                                    First Name *
                                </dt>
                                <dd>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.first_name,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.first_name"
                                        class="mt-2 text-sm text-red-600 flex items-center gap-1"
                                    >
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ form.errors.first_name }}
                                    </p>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2 mb-2">
                                    <i class="fas fa-user text-gray-400"></i>
                                    Last Name *
                                </dt>
                                <dd>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.last_name,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.last_name"
                                        class="mt-2 text-sm text-red-600 flex items-center gap-1"
                                    >
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ form.errors.last_name }}
                                    </p>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2 mb-2">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                    Email *
                                </dt>
                                <dd>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.email,
                                        }"
                                    />
                                    <p
                                        v-if="form.errors.email"
                                        class="mt-2 text-sm text-red-600 flex items-center gap-1"
                                    >
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ form.errors.email }}
                                    </p>
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
                        <dl class="space-y-5">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2 mb-2">
                                    <i class="fas fa-flag text-gray-400"></i>
                                    Country *
                                </dt>
                                <dd>
                                    <div class="relative">
                                                <select
                                                    id="country"
                                                    v-model="selectedCountry"
                                                    required
                                                    class="block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 py-3 pr-10 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                                    :class="{
                                                        'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors['address.country'],
                                                    }"
                                                >
                                            <option value="" disabled>Select a country...</option>
                                                    <option
                                                        v-for="country in countryOptions"
                                                        :key="country.code"
                                                        :value="country.code"
                                                    >
                                                        {{ country.name }}
                                                    </option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                                        </div>
                                    </div>
                                    <p
                                        v-if="form.errors['address.country']"
                                        class="mt-2 text-sm text-red-600 flex items-center gap-1"
                                    >
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ form.errors['address.country'] }}
                                    </p>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2 mb-2">
                                    <i class="fas fa-city text-gray-400"></i>
                                    City *
                                </dt>
                                <dd>
                                    <div class="relative">
                                        <select
                                            id="city"
                                            v-model="form.address.city"
                                            required
                                                    :disabled="!selectedCountry"
                                            class="block w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 py-3 pr-10 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            :class="{
                                                'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors['address.city'],
                                            }"
                                        >
                                                    <option value="" disabled>
                                                        {{ selectedCountry ? 'Select a city...' : 'Select a country first...' }}
                                                    </option>
                                                    <option
                                                        v-for="city in cityOptions"
                                                        :key="city"
                                                        :value="city"
                                                    >
                                                        {{ city }}
                                                    </option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                                        </div>
                                    </div>
                                    <p
                                        v-if="form.errors['address.city']"
                                        class="mt-2 text-sm text-red-600 flex items-center gap-1"
                                    >
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ form.errors['address.city'] }}
                                    </p>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2 mb-2">
                                    <i class="fas fa-mail-bulk text-gray-400"></i>
                                    Post Code *
                                </dt>
                                <dd>
                                    <input
                                        id="post_code"
                                        v-model.number="form.address.post_code"
                                        type="number"
                                        required
                                        min="0"
                                        step="1"
                                        class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors['address.post_code'],
                                        }"
                                    />
                                    <p
                                        v-if="form.errors['address.post_code']"
                                        class="mt-2 text-sm text-red-600 flex items-center gap-1"
                                    >
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ form.errors['address.post_code'] }}
                                    </p>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 flex items-center gap-2 mb-2">
                                    <i class="fas fa-road text-gray-400"></i>
                                    Street *
                                </dt>
                                <dd>
                                    <input
                                        id="street"
                                        v-model="form.address.street"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none"
                                        :class="{
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors['address.street'],
                                        }"
                                    />
                                    <p
                                        v-if="form.errors['address.street']"
                                        class="mt-2 text-sm text-red-600 flex items-center gap-1"
                                    >
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ form.errors['address.street'] }}
                                    </p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200/50">
                    <button
                        type="button"
                        @click="close"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
                    >
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 hover:shadow-indigo-600/40 hover:from-indigo-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                    >
                        <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                        <i v-else :class="isEditMode ? 'fas fa-save' : 'fas fa-plus'"></i>
                        {{ form.processing 
                            ? (isEditMode ? 'Updating...' : 'Creating...') 
                            : (isEditMode ? 'Update User' : 'Create User') 
                        }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

