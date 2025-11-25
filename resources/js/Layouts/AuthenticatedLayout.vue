<script setup>
import { ref } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
            <nav class="bg-white/80 backdrop-blur-lg border-b border-gray-200/50 shadow-sm relative z-50">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-20 justify-between items-center">
                        <div class="flex items-center flex-1">
                            <!-- Logo/Brand -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-3 group">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 text-white shadow-lg shadow-indigo-500/50 group-hover:shadow-indigo-600/50 transition-all duration-300 group-hover:scale-105">
                                        <i class="fas fa-users text-lg"></i>
                                    </div>
                                    <div class="hidden sm:block">
                                        <h1 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                            User Management
                                        </h1>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-1 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    <i class="fas fa-th-large mr-2"></i>
                                    Dashboard
                                </NavLink>
                            </div>

                            <!-- Page Title (from header slot) -->
                            <div v-if="$slots.header" class="hidden md:flex items-center ml-10 pl-10 border-l border-gray-200">
                                <slot name="header" />
                            </div>
                        </div>

                        <!-- Right side actions -->
                        <div class="flex items-center gap-4">
                            <!-- Right side slot for actions like notification bell -->
                            <slot name="right" />

                            <!-- Mobile hamburger -->
                            <div class="flex items-center sm:hidden">
                            <button
                                    @click="showingNavigationDropdown = !showingNavigationDropdown"
                                    class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <i 
                                        :class="showingNavigationDropdown ? 'fas fa-times' : 'fas fa-bars'"
                                        class="h-6 w-6"
                                    ></i>
                            </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden border-t border-gray-200/50 bg-white/95 backdrop-blur-sm"
                >
                    <div class="space-y-1 px-4 pb-4 pt-3">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            <i class="fas fa-th-large mr-2"></i>
                            Dashboard
                        </ResponsiveNavLink>
                    </div>
                </div>
            </nav>


            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
