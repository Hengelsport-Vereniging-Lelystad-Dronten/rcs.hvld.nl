<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import OvertredingForm from '@/Components/OvertredingForm.vue';

const props = defineProps({
    ronde: { type: Object, required: true },
    overtredingTypes: { type: Array, required: true },
    strafmaten: { type: Array, required: true },
    constateringWijzes: { type: Array, required: true },
    waters: { type: Array, required: true },
    statusOptions: { type: Array, required: true },
});

const waterNaam = computed(() => props.ronde?.water?.naam || 'Onbekend water');
const controllerNaam = computed(() => props.ronde?.user?.name || 'Onbekend');
const overtredingen = computed(() => props.ronde?.overtredingen || []);
const isGeenOvertreding = (overtreding) => {
    return String(overtreding.overtreding_type?.code ?? '').trim() === '00';
};
const gecontroleerdeVissers = computed(() => overtredingen.value.length);
const geregistreerdeOvertredingen = computed(
    () => overtredingen.value.filter((overtreding) => !isGeenOvertreding(overtreding)).length
);

const isActief = computed(() => props.ronde?.status === 'Actief');
const isDeleting = ref(false);
const isEditingRonde = ref(false);

const getLocalDateTime = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
};

const afrondForm = useForm({
    ronde_id: props.ronde.id,
    opmerkingen: props.ronde.opmerkingen || '',
    eind_tijd: getLocalDateTime(),
});

const rondeForm = useForm({
    water_id: props.ronde.water_id,
    start_tijd: props.ronde.start_tijd ? new Date(props.ronde.start_tijd).toISOString().slice(0, 16) : getLocalDateTime(),
    opmerkingen: props.ronde.opmerkingen || '',
    status: props.ronde.status || 'Actief',
});

const onOvertredingSuccess = () => {
    router.reload({ only: ['ronde'] });
};

const sluitRondeAf = () => {
    afrondForm.put(route('controles.afronden', afrondForm.ronde_id), {
        onSuccess: () => {
            router.reload({ only: ['ronde'] });
        },
    });
};

const annuleerRonde = () => {
    if (confirm('Weet je zeker dat je deze ronde wilt annuleren? Alle vastgelegde overtredingen gaan verloren.')) {
        isDeleting.value = true;
        router.delete(route('controles.destroy', props.ronde.id), {
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

const bewerkRonde = () => {
    isEditingRonde.value = true;
};

const annuleerBewerkRonde = () => {
    isEditingRonde.value = false;
    rondeForm.reset();
    rondeForm.clearErrors();
};

const slaRondeOp = () => {
    rondeForm.put(route('controles.update', props.ronde.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditingRonde.value = false;
            router.reload({ only: ['ronde'] });
        },
    });
};
</script>

<template>
    <Head :title="'Ronde: ' + waterNaam" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Controle Ronde: {{ waterNaam }}
                <span :class="['ml-3 px-3 py-1 text-sm font-bold rounded-full', isActief ? 'bg-green-600 text-white' : 'bg-blue-600 text-white']">
                    {{ isActief ? 'ACTIEF' : 'AFGEROND' }}
                </span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8 border-l-4 border-indigo-500">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Ronde Overzicht</h3>
                        <button
                            type="button"
                            @click="bewerkRonde"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded hover:bg-indigo-700"
                        >
                            Ronde Bewerken
                        </button>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt class="font-semibold text-gray-700">Water:</dt>
                        <dd>{{ waterNaam }}</dd>

                        <dt class="font-semibold text-gray-700">Controller:</dt>
                        <dd>{{ controllerNaam }}</dd>

                        <dt class="font-semibold text-gray-700">Start Tijd:</dt>
                        <dd>{{ ronde.start_tijd ? new Date(ronde.start_tijd).toLocaleString('nl-NL') : 'N.V.T.' }}</dd>

                        <dt class="font-semibold text-gray-700">Eind Tijd:</dt>
                        <dd>{{ ronde.eind_tijd ? new Date(ronde.eind_tijd).toLocaleString('nl-NL') : 'N.V.T.' }}</dd>
                        <dt class="font-semibold text-gray-700">Status:</dt>
                        <dd>{{ ronde.status }}</dd>
                    </dl>

                    <form v-if="isEditingRonde" @submit.prevent="slaRondeOp" class="mt-6 p-4 border border-indigo-200 rounded-md bg-indigo-50 space-y-4">
                        <div>
                            <InputLabel for="edit_water_id" value="Water" />
                            <select id="edit_water_id" v-model="rondeForm.water_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="water in waters" :key="water.id" :value="water.id">
                                    {{ water.naam }}
                                </option>
                            </select>
                            <InputError :message="rondeForm.errors.water_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="edit_start_tijd" value="Starttijd" />
                            <TextInput id="edit_start_tijd" type="datetime-local" v-model="rondeForm.start_tijd" class="mt-1 block w-full" />
                            <InputError :message="rondeForm.errors.start_tijd" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="edit_status" value="Status" />
                            <select id="edit_status" v-model="rondeForm.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option v-for="status in statusOptions" :key="status" :value="status">
                                    {{ status }}
                                </option>
                            </select>
                            <InputError :message="rondeForm.errors.status" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="edit_opmerkingen" value="Opmerkingen" />
                            <textarea
                                id="edit_opmerkingen"
                                v-model="rondeForm.opmerkingen"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                            ></textarea>
                            <InputError :message="rondeForm.errors.opmerkingen" class="mt-2" />
                        </div>

                        <div class="flex gap-2">
                            <PrimaryButton type="submit" :disabled="rondeForm.processing" class="bg-indigo-600 hover:bg-indigo-700">
                                Opslaan
                            </PrimaryButton>
                            <button
                                type="button"
                                @click="annuleerBewerkRonde"
                                class="inline-flex items-center px-3 py-2 bg-gray-200 text-gray-700 text-xs font-semibold rounded hover:bg-gray-300"
                            >
                                Annuleren
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Controle Registraties</h3>
                            <p class="text-sm text-gray-600">
                                Gecontroleerde vissers: {{ gecontroleerdeVissers }}
                                <span class="mx-2">•</span>
                                Geregistreerde overtredingen: {{ geregistreerdeOvertredingen }}
                            </p>
                        </div>
                        <span class="text-sm font-semibold text-gray-700">
                            {{ overtredingen.length }} registratie{{ overtredingen.length === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div v-if="overtredingen.length === 0" class="text-gray-500 italic p-4 border border-gray-100 rounded-md">
                        Nog geen overtredingen vastgelegd in deze ronde.
                    </div>

                    <ul v-else class="space-y-4">
                        <li
                            v-for="overtreding in overtredingen"
                            :key="overtreding.id"
                            :class="[
                                'p-4 rounded-lg transition duration-150',
                                isGeenOvertreding(overtreding)
                                    ? 'border border-emerald-200 bg-emerald-50 hover:bg-emerald-100'
                                    : 'border border-red-200 bg-red-50 hover:bg-red-100'
                            ]"
                        >
                            <p :class="['font-bold', isGeenOvertreding(overtreding) ? 'text-emerald-800' : 'text-red-800']">
                                <span
                                    :class="[
                                        'text-sm mr-2',
                                        isGeenOvertreding(overtreding) ? 'text-emerald-600' : 'text-red-600'
                                    ]"
                                >Overtreding:</span>
                                {{ overtreding.overtreding_type?.code || '-' }} - {{ overtreding.overtreding_type?.omschrijving || 'Onbekend' }}
                            </p>
                            <p class="text-sm mt-1 text-gray-700">
                                <span class="font-semibold">Maatregel:</span> {{ overtreding.genomen_maatregel || 'N.V.T.' }}
                            </p>
                            <p v-if="overtreding.vispasnummer" class="text-sm text-gray-700">
                                <span class="font-semibold">Vispasnr:</span> {{ overtreding.vispasnummer }}
                            </p>
                            <p v-if="overtreding.vispas_ingenomen" class="text-sm text-red-700 font-bold">
                                <span class="font-semibold">Status:</span> VISpas Ingenomen
                            </p>
                            <p v-if="overtreding.details" class="text-xs italic text-gray-600 mt-1">
                                <span class="font-semibold">Details:</span> {{ overtreding.details }}
                            </p>
                        </li>
                    </ul>
                </div>

                <div v-if="isActief" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg border-t-4 border-red-500">
                        <OvertredingForm
                            :ronde="ronde"
                            :overtreding-types="overtredingTypes"
                            :strafmaten="strafmaten"
                            :constatering-wijzes="constateringWijzes"
                            @success="onOvertredingSuccess"
                        />
                    </div>

                    <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg p-6 border-t-4 border-green-500">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Controle Ronde Afronden</h3>
                        <p class="text-sm text-gray-600 mb-6">Sluit de ronde af om de gegevens definitief op te slaan en te archiveren.</p>

                        <form @submit.prevent="sluitRondeAf">
                            <div class="mb-4">
                                <InputLabel for="eind_tijd" value="Eindtijd" />
                                <TextInput id="eind_tijd" type="datetime-local" class="mt-1 block w-full" v-model="afrondForm.eind_tijd" required />
                                <InputError class="mt-2" :message="afrondForm.errors.eind_tijd" />
                            </div>

                            <div class="mb-6">
                                <InputLabel for="opmerkingen_ronde" value="Algemene Opmerkingen Ronde (Optioneel)" />
                                <textarea
                                    id="opmerkingen_ronde"
                                    v-model="afrondForm.opmerkingen"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                                    :disabled="afrondForm.processing || !isActief"
                                    placeholder="Eventuele opmerkingen over de gehele ronde."
                                ></textarea>
                                <InputError :message="afrondForm.errors.opmerkingen" class="mt-2" />
                            </div>

                            <PrimaryButton
                                type="submit"
                                :class="{ 'opacity-25': afrondForm.processing }"
                                :disabled="afrondForm.processing || !isActief"
                                class="w-full justify-center bg-green-600 hover:bg-green-700 active:bg-green-800"
                            >
                                <span v-if="afrondForm.processing">Bezig met afronden...</span>
                                <span v-else>Ronde Definitief Afronden</span>
                            </PrimaryButton>
                        </form>

                        <div v-if="overtredingen.length === 0" class="mt-8 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-700 mb-2">Ronde Permanent Verwijderen</h4>
                            <p class="text-xs text-red-500 mb-3">
                                <span class="font-bold">Waarschuwing:</span> Hiermee wordt de ronde permanent verwijderd, inclusief overtredingen.
                            </p>

                            <button
                                @click="annuleerRonde"
                                :disabled="isDeleting || !isActief"
                                type="button"
                                :class="{ 'opacity-25': isDeleting || !isActief }"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:bg-gray-400"
                            >
                                {{ isDeleting ? 'Bezig met annuleren...' : 'Annuleer Ronde' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-blue-50 border border-blue-200 text-blue-800 p-6 rounded-lg shadow-md mt-8">
                    <p class="font-bold text-lg">Ronde Afgerond</p>
                    <p class="text-sm mt-1">Deze ronde is definitief afgesloten en gearchiveerd. Er kunnen geen nieuwe overtredingen meer aan worden toegevoegd.</p>
                    <p v-if="ronde.opmerkingen" class="mt-3 text-sm italic border-t border-blue-200 pt-2">Algemene Opmerkingen: {{ ronde.opmerkingen }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
