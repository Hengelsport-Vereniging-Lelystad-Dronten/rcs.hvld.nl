<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue'; // Assuming you have a Pagination component

const props = defineProps({
    logs: Object,
    filters: Object,
    users: Array,
});

// Reactive state for filters
const form = ref({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    user_id: props.filters.user_id || '',
    event: props.filters.event || '',
    resource_id: props.filters.resource_id || '',
});

// Watch for changes in the form and trigger a reload
watch(form, (newForm) => {
    router.get(route('beheer.auditlog.index'), newForm, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

const getEventClass = (event) => {
    switch (event) {
        case 'created':
            return 'bg-green-100 text-green-800';
        case 'updated':
            return 'bg-yellow-100 text-yellow-800';
        case 'deleted':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Audit Log" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Log</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <input type="date" v-model="form.date_from" placeholder="From Date" class="form-input rounded-md shadow-sm">
                        <input type="date" v-model="form.date_to" placeholder="To Date" class="form-input rounded-md shadow-sm">
                        <select v-model="form.user_id" class="form-select rounded-md shadow-sm">
                            <option value="">All Users</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                        <input type="text" v-model="form.event" placeholder="Event (e.g. created)" class="form-input rounded-md shadow-sm">
                        <input type="text" v-model="form.resource_id" placeholder="Resource ID" class="form-input rounded-md shadow-sm">
                    </div>

                    <div class="hidden md:grid grid-cols-5 gap-4 px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 rounded-t-lg">
                        <div>Description</div>
                        <div>User</div>
                        <div>Subject</div>
                        <div>Event</div>
                        <div>Date</div>
                    </div>

                    <div class="divide-y divide-gray-200 border-b border-gray-200">
                        <div v-for="log in logs.data" :key="log.id" class="p-4 md:py-3 md:px-4 hover:bg-gray-50 transition duration-150">
                             <div class="grid grid-cols-1 md:grid-cols-5 gap-y-3 md:gap-4 items-center">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Description</p>
                                    <span class="font-medium text-gray-900">{{ log.description }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">User</p>
                                    <span class="text-sm text-gray-700">{{ log.causer ? log.causer.name : 'System' }}</span>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Subject</p>
                                    <span v-if="log.subject" class="text-sm text-gray-700">{{ log.subject_type }} #{{ log.subject_id }}</span>
                                     <span v-else class="text-sm text-gray-500">-</span>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Event</p>
                                    <span :class="getEventClass(log.event)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ log.event }}
                                    </span>
                                </div>
                                <div>
                                     <p class="text-xs font-medium text-gray-500 uppercase mb-1 md:hidden">Date</p>
                                    <span class="text-sm text-gray-700">{{ new Date(log.created_at).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                         <p v-if="logs.data.length === 0" class="p-4 text-gray-500">No log entries found.</p>
                    </div>
                    <Pagination :links="logs.links" class="mt-6" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
