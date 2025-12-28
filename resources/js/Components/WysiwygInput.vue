<script setup>
import { ref, watch, onMounted } from 'vue';

// Props definitie: modelValue is de standaard prop voor v-model binding in Vue 3
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

// Emits definitie: hiermee sturen we updates terug naar de ouder-component
const emit = defineEmits(['update:modelValue']);

// Referentie naar het DOM-element van de editor (de div met contenteditable)
const editor = ref(null);

// Watcher: Update de editor inhoud als de prop extern wijzigt (bijv. bij laden van data)
// We checken of de inhoud verschilt om oneindige loops en cursor-springs te voorkomen
watch(() => props.modelValue, (newValue) => {
    if (editor.value && editor.value.innerHTML !== newValue) {
        editor.value.innerHTML = newValue || '';
    }
});

// Lifecycle hook: Zet de initiële waarde in de editor zodra deze gemount is
onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = props.modelValue || '';
    }
});

// Event handler: Wordt aangeroepen bij elke toetsaanslag of wijziging in de editor
// Stuurt de huidige HTML-inhoud terug naar de ouder via het update:modelValue event
const updateValue = () => {
    emit('update:modelValue', editor.value.innerHTML);
};

// Helper functie voor het uitvoeren van browser commando's (zoals 'bold', 'italic')
// document.execCommand is verouderd maar werkt nog steeds breed voor simpele editors
const exec = (command, value = null) => {
    document.execCommand(command, false, value);
    editor.value.focus(); // Zorg dat de focus terugkeert naar de editor na klikken op een knop
};
</script>

<template>
    <div class="border border-gray-300 rounded-md shadow-sm bg-white overflow-hidden focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
        <!-- Toolbar met opmaak knoppen -->
        <div class="flex items-center gap-1 p-2 bg-gray-50 border-b border-gray-200">
            <button type="button" @click.prevent="exec('bold')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 font-bold min-w-[30px]" title="Vetgedrukt">B</button>
            <button type="button" @click.prevent="exec('italic')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 italic min-w-[30px]" title="Cursief">I</button>
            <button type="button" @click.prevent="exec('underline')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 underline min-w-[30px]" title="Onderstreept">U</button>
            <div class="w-px h-4 bg-gray-300 mx-1"></div>
            <button type="button" @click.prevent="exec('insertUnorderedList')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 px-2" title="Lijst">• Lijst</button>
            <button type="button" @click.prevent="exec('insertOrderedList')" class="p-1.5 rounded hover:bg-gray-200 text-gray-700 px-2" title="Nummering">1. Lijst</button>
        </div>
        
        <!-- Editor Gebied: contenteditable div die fungeert als input veld -->
        <!-- 'prose' classes komen van Tailwind Typography plugin voor nette standaard opmaak -->
        <div 
            ref="editor"
            class="p-3 min-h-[150px] outline-none prose prose-sm max-w-none"
            contenteditable="true"
            @input="updateValue"
        ></div>
    </div>
</template>