<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    faqs: Array
});

const openFaq = ref(null);
const searchQuery = ref('');

const toggle = (id) => {
    openFaq.value = openFaq.value === id ? null : id;
};

const filteredFaqs = computed(() => {
    if (!searchQuery.value) return props.faqs;
    const query = searchQuery.value.toLowerCase();
    return props.faqs.filter(faq => 
        faq.question.toLowerCase().includes(query) || 
        faq.answer.toLowerCase().includes(query)
    );
});
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
                
                <!-- Zoekbalk -->
                <div class="mb-6">
                    <TextInput 
                        v-model="searchQuery" 
                        placeholder="Zoek in vragen en antwoorden..." 
                        class="w-full"
                    />
                </div>

                <div class="space-y-4">
                    <div v-for="faq in filteredFaqs" :key="faq.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <button @click="toggle(faq.id)" class="w-full text-left px-6 py-4 focus:outline-none flex justify-between items-center">
                            <span class="font-bold text-gray-800">{{ faq.question }}</span>
                            <span class="text-gray-500 text-xl">{{ openFaq === faq.id ? '−' : '+' }}</span>
                        </button>
                        <div v-show="openFaq === faq.id" class="px-6 pb-4 text-gray-600 border-t border-gray-100 pt-4">
                            <div class="prose max-w-none" v-html="faq.answer"></div>
                        </div>
                    </div>

                    <div v-if="filteredFaqs.length === 0" class="text-center text-gray-500 py-8">
                        {{ faqs.length === 0 ? 'Er zijn nog geen veelgestelde vragen toegevoegd.' : 'Geen resultaten gevonden voor je zoekopdracht.' }}
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>