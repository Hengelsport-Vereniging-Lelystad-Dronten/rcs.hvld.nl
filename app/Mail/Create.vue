<script setup>
import { useForm, Head } from '@inertiajs/vue3';

const form = useForm({
    voornaam: '',
    achternaam: '',
    geboortedatum: '',
    adres: '',
    postcode: '',
    woonplaats: '',
    telefoonnummer: '',
    email: '',
    lidnummer: '',
    akkoord_lidmaatschap: false,
    akkoord_vog: false,
    motivatie_tekst: '',
    motivatie_keuzes: [],
    ervaring_tekst: '',
});

const submit = () => {
    form.post(route('aanmelden.store'));
};
</script>

<template>
    <Head title="Aanmelden Sportvisserijcontroleur" />

    <div class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12">
        <div class="relative py-3 sm:max-w-4xl sm:mx-auto w-full px-4 sm:px-0">
            <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
                
                <div class="mb-10 text-center">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Word Sportvisserijcontroleur</h1>
                    <p class="text-xl text-blue-600 font-medium italic">"Help mee en houd onze wateren bevisbaar"</p>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    
                    <!-- Persoonsgegevens -->
                    <section>
                        <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Persoonsgegevens</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Voornaam</label>
                                <input v-model="form.voornaam" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.voornaam" class="text-red-500 text-sm mt-1">{{ form.errors.voornaam }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Achternaam</label>
                                <input v-model="form.achternaam" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.achternaam" class="text-red-500 text-sm mt-1">{{ form.errors.achternaam }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Geboortedatum</label>
                                <input v-model="form.geboortedatum" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.geboortedatum" class="text-red-500 text-sm mt-1">{{ form.errors.geboortedatum }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">HVLD Lidnummer</label>
                                <input v-model="form.lidnummer" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Indien bekend" />
                                <div v-if="form.errors.lidnummer" class="text-red-500 text-sm mt-1">{{ form.errors.lidnummer }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Adres</label>
                                <input v-model="form.adres" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.adres" class="text-red-500 text-sm mt-1">{{ form.errors.adres }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Postcode</label>
                                <input v-model="form.postcode" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.postcode" class="text-red-500 text-sm mt-1">{{ form.errors.postcode }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Woonplaats</label>
                                <input v-model="form.woonplaats" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.woonplaats" class="text-red-500 text-sm mt-1">{{ form.errors.woonplaats }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Telefoonnummer</label>
                                <input v-model="form.telefoonnummer" type="tel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.telefoonnummer" class="text-red-500 text-sm mt-1">{{ form.errors.telefoonnummer }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">E-mailadres</label>
                                <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>
                        </div>
                    </section>

                    <!-- Motivatie -->
                    <section>
                        <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Motivatie & Interesse</h2>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Wat spreekt je aan in de rol van controleur?</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" v-model="form.motivatie_keuzes" value="Toezicht houden aan de waterkant" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-gray-700">Toezicht houden aan de waterkant</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" v-model="form.motivatie_keuzes" value="Contact met andere vissers" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-gray-700">Contact met andere vissers</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" v-model="form.motivatie_keuzes" value="Bijdragen aan de vereniging" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-gray-700">Bijdragen aan de vereniging</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" v-model="form.motivatie_keuzes" value="Natuur en buiten zijn" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-gray-700">Natuur en buiten zijn</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Waarom wil je controleur worden?</label>
                            <textarea v-model="form.motivatie_tekst" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Vertel kort iets over je motivatie..." required></textarea>
                            <div v-if="form.errors.motivatie_tekst" class="text-red-500 text-sm mt-1">{{ form.errors.motivatie_tekst }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ervaring (Sportvissen / Vrijwilligerswerk)</label>
                            <textarea v-model="form.ervaring_tekst" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Heb je al ervaring met controleren of ander vrijwilligerswerk?"></textarea>
                        </div>
                    </section>

                    <!-- Voorwaarden & Afronding -->
                    <section class="bg-blue-50 p-6 rounded-lg border border-blue-100">
                        <h3 class="text-lg font-semibold text-blue-800 mb-3">Procedure & Voorwaarden</h3>
                        <ul class="list-disc pl-5 text-sm text-blue-800 mb-6 space-y-1">
                            <li>Na aanmelding volgt een <strong>intakegesprek</strong>.</li>
                            <li>Er geldt een proefperiode van drie maanden.</li>
                            <li>Na de opleiding en proefperiode wordt de officiële controleurspas uitgegeven.</li>
                            <li>Eventuele kosten voor de opleiding worden vergoed door het bestuur.</li>
                            <li>Een Verklaring Omtrent het Gedrag (VOG) is verplicht (kosten worden vergoed).</li>
                        </ul>

                        <div class="space-y-3">
                            <label class="flex items-start space-x-3">
                                <input type="checkbox" v-model="form.akkoord_lidmaatschap" class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                                <span class="text-sm text-gray-700">Ik ben lid van HVLD (of bereid lid te worden) en ga akkoord met de voorwaarden.</span>
                            </label>
                            <div v-if="form.errors.akkoord_lidmaatschap" class="text-red-500 text-sm ml-7">{{ form.errors.akkoord_lidmaatschap }}</div>

                            <label class="flex items-start space-x-3">
                                <input type="checkbox" v-model="form.akkoord_vog" class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" required>
                                <span class="text-sm text-gray-700">Ik ben bereid een VOG (Verklaring Omtrent het Gedrag) aan te vragen.</span>
                            </label>
                            <div v-if="form.errors.akkoord_vog" class="text-red-500 text-sm ml-7">{{ form.errors.akkoord_vog }}</div>
                        </div>
                    </section>

                    <div class="flex justify-end pt-4">
                        <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-150 ease-in-out disabled:opacity-50">
                            Aanmelding Versturen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
