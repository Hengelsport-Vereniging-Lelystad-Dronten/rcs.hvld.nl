<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import LocationPickerMap from '@/Components/LocationPickerMap.vue';

// De pagina krijgt het water object (indien bestaand) via props
const props = defineProps({
    water: {
        type: Object,
        default: () => ({
            id: null,
            naam: '',
            beschrijving: '',
            latitude: 52.5261545, // Default to Lelystad
            longitude: 5.4729717, // Default to Lelystad
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

const updateLocation = (location) => {
    if (location && typeof location.lat !== 'undefined' && typeof location.lng !== 'undefined') {
        form.latitude = location.lat;
        form.longitude = location.lng;
    }
};

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
                        
                        <!-- GPS Coördinaten (Kaart Selectie) -->
                        <div class="pt-4">
                            <InputLabel value="Selecteer Locatie op de Kaart" />
                            <p class="text-sm text-gray-500 mb-2">
                                Klik op de kaart om een locatie te selecteren of sleep de marker naar de juiste plek.
                            </p>
                            <LocationPickerMap 
                                :latitude="form.latitude"
                                :longitude="form.longitude"
                                @update:location="updateLocation"
                            />
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <InputLabel for="latitude" value="Latitude (automatisch)" />
                                    <TextInput
                                        id="latitude"
                                        v-model="form.latitude"
                                        type="text"
                                        class="mt-1 block w-full bg-gray-100"
                                        readonly
                                    />
                                    <InputError class="mt-2" :message="form.errors.latitude" />
                                </div>
                                <div>
                                    <InputLabel for="longitude" value="Longitude (automatisch)" />
                                    <TextInput
                                        id="longitude"
                                        v-model="form.longitude"
                                        type="text"
                                        class="mt-1 block w-full bg-gray-100"
                                        readonly
                                    />
                                    <InputError class="mt-2" :message="form.errors.longitude" />
                                </div>
                            </div>
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