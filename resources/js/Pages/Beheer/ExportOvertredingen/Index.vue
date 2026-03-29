<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    nietGeexporteerdCount: Number,
    geexporteerdCount: Number,
    csrf_token: String,
});

const page = usePage();

// Haal zowel succes- als foutmeldingen op
const flashMessage = computed(() => {
    return page.props.flash.success || page.props.flash.message;
});

const flashError = computed(() => {
    return page.props.flash.error;
});

// Functie voor bevestiging van reset
const confirmReset = () => {
    if (confirm('Weet u zeker dat u de export status wilt resetten voor alle overtredingen? Dit kan niet ongedaan worden gemaakt.')) {
        event.target.submit();
    }
};
</script>

<template>
    <Head title="Export Overtredingen" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Export Overtredingen</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Flash Success Message -->
                <div v-if="flashMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg shadow">
                    {{ flashMessage }}
                </div>

                <!-- Flash Error Message -->
                <div v-if="flashError" class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg shadow">
                    {{ flashError }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Overzicht</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ nietGeexporteerdCount }}</div>
                                <div class="text-sm text-blue-800">Niet-geëxporteerde actieve overtredingen</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ geexporteerdCount }}</div>
                                <div class="text-sm text-green-800">Reeds geëxporteerde actieve overtredingen</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Export Opties</h3>

                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Standaard Export</h4>
                                <p class="text-sm text-gray-600 mb-3">
                                    Exporteert alleen niet eerder geëxporteerde actieve overtredingen.
                                    Na export worden deze gemarkeerd als geëxporteerd.
                                </p>
                                <a
                                    :href="route('beheer.export-overtredingen.pdf', { force_re_export: false })"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :class="{ 'opacity-50 cursor-not-allowed': nietGeexporteerdCount === 0 }"
                                    download
                                >
                                    Export PDF ({{ nietGeexporteerdCount }} overtredingen)
                                </a>
                            </div>

                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Her-export (Force Re-export)</h4>
                                <p class="text-sm text-gray-600 mb-3">
                                    Exporteert alle actieve overtredingen, inclusief eerder geëxporteerde.
                                    Gebruikt voor correcties of wanneer alle data nodig is.
                                    Overtredingen worden NIET opnieuw gemarkeerd.
                                </p>
                                <a
                                    :href="route('beheer.export-overtredingen.pdf', { force_re_export: true })"
                                    class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :class="{ 'opacity-50 cursor-not-allowed pointer-events-none': (nietGeexporteerdCount + geexporteerdCount) === 0 }"
                                    download
                                >
                                    Force Re-export PDF ({{ nietGeexporteerdCount + geexporteerdCount }} overtredingen)
                                </a>
                            </div>
                            <div class="bg-red-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Reset Export Status (Gevaarlijk)</h4>
                                <p class="text-sm text-gray-600 mb-3">
                                    Reset de export status voor alle overtredingen. Gebruik dit alleen bij correcties of wanneer alle overtredingen opnieuw geëxporteerd moeten worden.
                                </p>
                                <form method="POST" :action="route('beheer.export-overtredingen.reset')" @submit.prevent="confirmReset">
                                    <input type="hidden" name="_token" :value="csrf_token">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Reset Export Status
                                    </button>
                                </form>
                            </div>
                            <h4 class="font-medium text-gray-900 mb-2">Informatie</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• De export bevat een volledige 7W-rapportage voor elke overtreding</li>
                                <li>• Alleen actieve overtredingen worden geëxporteerd</li>
                                <li>• Geannuleerde overtredingen worden uitgesloten</li>
                                <li>• De export wordt gelogd in het audit log</li>
                                <li>• PDF's zijn geschikt voor externe partijen zoals Sportvisunie</li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>