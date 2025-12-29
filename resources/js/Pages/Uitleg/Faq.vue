<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    faqs: Array
});

const openFaq = ref(null);

const toggle = (id) => {
    openFaq.value = openFaq.value === id ? null : id;
};
</script>

<template>
    <Head title="Veelgestelde Vragen" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Veelgestelde Vragen (FAQ)</h2>
                <Link :href="route('uitleg.index')" class="text-sm text-gray-600 hover:text-gray-900">← Terug naar overzicht</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="space-y-4">
                    <div v-for="faq in faqs" :key="faq.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <button @click="toggle(faq.id)" class="w-full text-left px-6 py-4 focus:outline-none flex justify-between items-center">
                            <span class="font-bold text-gray-800">{{ faq.question }}</span>
                            <span class="text-gray-500 text-xl">{{ openFaq === faq.id ? '−' : '+' }}</span>
                        </button>
                        <div v-show="openFaq === faq.id" class="px-6 pb-4 text-gray-600 border-t border-gray-100 pt-4">
                            <div class="prose max-w-none" v-html="faq.answer"></div>
                        </div>
                    </div>

                    <div v-if="faqs.length === 0" class="text-center text-gray-500 py-8">
                        Er zijn nog geen veelgestelde vragen toegevoegd.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>