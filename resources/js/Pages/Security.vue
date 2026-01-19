<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Beveiliging & Responsible Disclosure" />

    <div class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12">
        <div class="relative py-3 sm:max-w-4xl sm:mx-auto w-full px-4 sm:px-0">
            <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
                <div class="mx-auto prose max-w-none text-gray-700">
                    
                    <h1 class="text-3xl font-bold mb-6 text-gray-900">Verklaring Verantwoorde Openbaarmaking en Beveiligingsbeleid</h1>

                    <p class="mb-4">
                        De beveiliging van onze systemen heeft de hoogste prioriteit. Hoeveel moeite wij ook steken in systeembeveiliging, er kunnen altijd kwetsbaarheden aanwezig zijn. Als u een kwetsbaarheid ontdekt, dan horen wij dit graag zodat wij stappen kunnen ondernemen om deze aan te pakken. Wij vragen u om ons te helpen onze gebruikers en onze systemen te beschermen, in lijn met de principes van verantwoorde open source beveiligingspraktijken, zoals besproken in bronnen zoals de <a href="https://opensource.guide/security/" target="_blank" class="text-blue-600 hover:underline">Open Source Guides</a>.
                    </p>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Melden van een Kwetsbaarheid</h2>
                        <p class="mb-4">
                            Voor alle beveiligingsgerelateerde bugs, stuur een e-mail naar <strong><a href="mailto:rcs@hvld.nl" class="text-blue-600 hover:underline">rcs@hvld.nl</a></strong>.
                        </p>
                        <p>
                            Maak deze details alstublieft niet openbaar zonder eerst contact met ons op te nemen. Als tegenprestatie zullen wij alle ontvangen kwetsbaarheidsrapporten onderzoeken en, waar nodig, de juiste maatregelen nemen zodra dit gerechtvaardigd is. In de meeste gevallen zal dit eerst een workaround/mitigatie zijn, gevolgd door een fix en, indien nodig, een publieke openbaarmaking.
                        </p>
                    </section>

                    <hr class="my-8 border-gray-200">

                    <div class="grid md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-xl font-medium mb-2 text-green-700">Wel Doen:</h3>
                            <ul class="list-disc pl-5 space-y-1 text-sm">
                                <li>Meld de kwetsbaarheid zo snel als redelijkerwijs mogelijk is, om het risico dat vijandige actoren deze vinden en er misbruik van maken te minimaliseren.</li>
                                <li>Meld op een manier die de vertrouwelijkheid van het rapport waarborgt, zodat anderen geen toegang krijgen tot de informatie.</li>
                                <li>Verstrek voldoende informatie om het probleem te reproduceren, zodat wij het kunnen oplossen. Gewoonlijk volstaat het IP-adres of de URL van het getroffen systeem en een beschrijving van de kwetsbaarheid. Bij complexe kwetsbaarheden kan verdere uitleg nodig zijn.</li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xl font-medium mb-2 text-red-700">Niet Doen:</h3>
                            <ul class="list-disc pl-5 space-y-1 text-sm">
                                <li>Onthul de kwetsbaarheid of het probleem aan anderen voordat het is opgelost.</li>
                                <li>Bouw uw eigen achterdeur in een informatiesysteem met de intentie deze vervolgens te gebruiken om de kwetsbaarheid aan te tonen, omdat dit extra schade kan veroorzaken en onnodige beveiligingsrisico's creëert.</li>
                                <li>Gebruik een kwetsbaarheid verder dan nodig om het bestaan ervan vast te stellen.</li>
                                <li>Kopieer, wijzig of verwijder data op het systeem. Een alternatief hiervoor is het maken van een directory listing van het systeem.</li>
                                <li>Breng geen wijzigingen aan in het systeem.</li>
                                <li>Krijg herhaaldelijk toegang tot het systeem of deel de toegang met anderen.</li>
                                <li>Gebruik brute force-aanvallen, aanvallen op fysieke beveiliging, social engineering, distributed denial of service (DDoS), spam of applicaties van derden om toegang tot het systeem te verkrijgen.</li>
                            </ul>
                        </div>
                    </div>

                    <hr class="my-8 border-gray-200">

                    <section class="mb-8">
                        <h3 class="text-xl font-medium mb-4 text-gray-800">Wat Wij Beloven</h3>
                        <ul class="list-disc pl-5 mb-4 space-y-2">
                            <li>Wij streven ernaar om binnen <strong>1 werkdag</strong> op uw melding te reageren met onze evaluatie van het rapport en een verwachte oplossingsdatum.</li>
                            <li>Als u de bovenstaande instructies hebt opgevolgd, zullen wij <strong>geen juridische stappen</strong> tegen u ondernemen met betrekking tot het rapport.</li>
                            <li>Wij zullen uw persoonlijke gegevens niet aan derden doorgeven zonder uw toestemming, tenzij dit noodzakelijk is om te voldoen aan een wettelijke verplichting. Melden onder een pseudoniem of anoniem is mogelijk.</li>
                            <li>Wij houden u op de hoogte van de voortgang bij het oplossen van het probleem.</li>
                            <li>In de publieke informatie over het gemelde probleem zullen wij uw naam vermelden als de ontdekker van het probleem (tenzij u anders wenst).</li>
                        </ul>
                        <p class="mt-4">Wij streven ernaar om alle problemen zo snel mogelijk op te lossen en spelen graag een actieve rol in de uiteindelijke publicatie over het probleem nadat het is opgelost.</p>
                    </section>

                    <hr class="my-8 border-gray-200">

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Lijst van Erkenning (Hall of Fame)</h2>
                        <p class="mb-4">
                            Deze sectie zal personen publiekelijk erkennen die op verantwoorde wijze beveiligingskwetsbaarheden in onze systemen hebben gemeld, waardoor de beveiliging wordt verbeterd. Bedankt voor uw waardevolle bijdragen!
                        </p>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-center italic text-gray-500">
                            Nog geen vermeldingen.
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Externe Bronnen</h2>
                        <p>
                            Voor verdere informatie over beveiligingspraktijken binnen open source projecten, raden wij u aan de <a href="https://opensource.guide/security/" target="_blank" class="text-blue-600 hover:underline">Open Source Guides</a> te raadplegen.
                        </p>
                    </section>

                    <div class="text-xs text-gray-400 mt-8">
                        <p>
                            Dit Verklaring Verantwoorde Openbaarmaking is gebaseerd op een voorbeeld geschreven door Floor Terra en de 
                            <a href="https://english.ncsc.nl/publications/publications/2019/juni/01/coordinated-vulnerability-disclosure-the-guideline" target="_blank" class="hover:underline">Richtlijn Coordinated Vulnerability Disclosure van het NCSC</a>.
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="mt-12 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
                        <Link href="/privacy" class="text-blue-600 hover:underline mx-2">Privacyverklaring</Link>
                        <span class="text-gray-300">|</span>
                        <Link href="/security" class="text-blue-600 hover:underline mx-2">Beveiliging & Responsible Disclosure</Link>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>