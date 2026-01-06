<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
});

const deleteFaq = (id) => {
    if (confirm('Weet je zeker dat je dit FAQ-item wilt verwijderen?')) {
        router.delete(route('beheer.faqs.destroy', id));
    }
};
</script>

<template>
    <Head title="Beheer FAQ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Beheer Veelgestelde Vragen
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Overzicht FAQ Items</h3>
                        <Link :href="route('beheer.faqs.create')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Nieuwe Vraag
                        </Link>
                    </div>

                    <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        {{ $page.props.flash.success }}
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Volgorde</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vraag</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Acties</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="faq in faqs" :key="faq.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        {{ faq.order }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                        {{ faq.question }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', faq.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                            {{ faq.is_active ? 'Actief' : 'Inactief' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <Link :href="route('beheer.faqs.edit', faq.id)" class="text-indigo-600 hover:text-indigo-900">Bewerken</Link>
                                        <button @click="deleteFaq(faq.id)" class="text-red-600 hover:text-red-900">Verwijderen</button>
                                    </td>
                                </tr>
                                <tr v-if="faqs.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 italic">
                                        Geen FAQ items gevonden.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>