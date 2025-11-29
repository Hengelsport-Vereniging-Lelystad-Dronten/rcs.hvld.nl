<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Verwijder Account
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Zodra je account is verwijderd, worden alle bijbehorende resources en gegevens permanent verwijderd. Download eventueel eerst gegevens of informatie die je wilt behouden voordat je je account verwijdert.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Verwijder Account</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-lg font-medium text-gray-900"
                >
                    Weet je zeker dat je je account wilt verwijderen?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Zodra je account is verwijderd, worden alle bijbehorende gegevens en resources permanent verwijderd. Voer je wachtwoord in om te bevestigen dat je je account definitief wilt verwijderen.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Wachtwoord"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Wachtwoord"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        Annuleer
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Verwijder Account
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
