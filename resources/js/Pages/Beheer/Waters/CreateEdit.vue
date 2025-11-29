<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
// WaterBoundaryMap is verwijderd omdat de kaart niet werkt en we alleen GPS-coördinaten nodig hebben.
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue'; 

// De pagina krijgt het water object (indien bestaand) via props
const props = defineProps({
    water: {
        type: Object,
        default: () => ({ 
            id: null, 
            naam: '', 
            beschrijving: '', 
            latitude: null, // GPS Latitude
            longitude: null, // GPS Longitude
        }),
    },
});

const isEdit = !!props.water.id;

// Formulier voor alle watergegevens (Naam, Beschrijving, GPS)
const form = useForm({
    naam: props.water.naam,
    beschrijving: props.water.beschrijving,
    latitude: props.water.latitude,     // GPS Latitude
    longitude: props.water.longitude,   // GPS Longitude
});

// De updateGps functie is niet meer nodig, omdat we de kaart verwijderd hebben.

const submit = () => {
    // Stuur alle gegevens (Naam, Beschrijving, GPS) in één keer
    if (isEdit) {
        form.put(route('beheer.waters.update', props.water.id), {
            onSuccess: () => console.log('Waterlocatie succesvol bijgewerkt!'),
        });
    } else {
        form.post(route('beheer.waters.store'), {
            onSuccess: () => console.log('Nieuwe waterlocatie succesvol aangemaakt!'),
        });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Water Locatie Bewerken' : 'Nieuwe Water Locatie'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ isEdit ? 'Water Locatie Bewerken' : 'Nieuwe Water Locatie' }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Sectie: Water Gegevens en Locatie -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ isEdit ? 'Locatie en Gegevens Aanpassen' : 'Nieuwe Locatie Toevoegen' }}</h3>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Naam Veld -->
                        <div>
                            <InputLabel for="naam" value="Naam van het Water" />
                            <TextInput
                                id="naam"
                                v-model="form.naam"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.naam" />
                        </div>

                        <!-- Beschrijving Veld -->
                        <div>
                            <InputLabel for="beschrijving" value="Beschrijving (Optioneel)" />
                            <textarea
                                id="beschrijving"
                                v-model="form.beschrijving"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.beschrijving" />
                        </div>
                        
                        <!-- GPS Coördinaten (Handmatige Invoer) -->
                        <div class="pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Latitude Veld -->
                            <div>
                                <InputLabel for="latitude" value="Latitude (Breedtegraad) - Vereist" />
                                <TextInput
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="52.1326"
                                />
                                <InputError class="mt-2" :message="form.errors.latitude" />
                            </div>

                            <!-- Longitude Veld -->
                            <div>
                                <InputLabel for="longitude" value="Longitude (Lengtegraad) - Vereist" />
                                <TextInput
                                    id="longitude"
                                    v-model="form.longitude"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="5.2913"
                                />
                                <InputError class="mt-2" :message="form.errors.longitude" />
                            </div>
                        </div>

                        <!-- Instructies en Link voor GPS-Coördinaten -->
                        <div class="pt-6 border-t border-gray-200">
                            <h4 class="text-md font-medium text-gray-700 mb-2">GPS Coördinaten Bepalen</h4>
                            <p class="text-sm text-gray-500 mb-2">
                                Gebruik de onderstaande externe tool om de exacte Latitude en Longitude voor de locatie te vinden.
                                Kopieer de waarden en plak deze in de velden hierboven.
                            </p>
                            
                            <a 
                                href="https://onlinecompass.net/nl/gps-coordinates" 
                                target="_new" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150 shadow-md"
                            >
                                Open GPS Coördinaten Tool
                            </a>
                        </div>

                        <div class="flex items-center gap-4 pt-6">
                            <PrimaryButton :disabled="form.processing">
                                {{ isEdit ? 'Locatie Opslaan' : 'Locatie Aanmaken' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>