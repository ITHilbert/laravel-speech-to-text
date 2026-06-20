<template>
    <button
        type="button"
        @click="toggle"
        :disabled="busy || !supported"
        :title="buttonTitle"
        :class="[
            'inline-flex items-center justify-center rounded-md border px-2.5 py-1.5 text-xs font-medium transition',
            recording
                ? 'border-red-300 bg-red-50 text-red-700 animate-pulse'
                : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50',
            (busy || !supported) ? 'opacity-50 cursor-not-allowed' : '',
        ]"
    >
        <svg v-if="!busy" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
        </svg>
        <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span class="ml-1.5">{{ label }}</span>
    </button>
</template>

<script>
import { ref, computed, onBeforeUnmount } from 'vue';

/**
 * Diktier-Button. Kennt zwei Modi (vom Package über die Prop `mode` gesteuert):
 *   - 'client': nutzt die Web Speech API direkt im Browser (kein Upload).
 *   - 'server': nimmt Audio auf und lädt es an `transcribeUrl` hoch (Whisper/OpenAI).
 *
 * Liefert erkannten Text über v-model (update:modelValue) bzw. das Event 'transcribed'.
 */
export default {
    name: 'SpeechInput',

    props: {
        modelValue: { type: String, default: '' },
        mode: { type: String, default: 'server' }, // 'server' | 'client'
        transcribeUrl: { type: String, default: '/speech/transcribe' },
        language: { type: String, default: 'de-DE' },
        appendMode: { type: Boolean, default: true }, // anhängen statt ersetzen
    },

    emits: ['update:modelValue', 'transcribed', 'error'],

    setup(props, { emit }) {
        const recording = ref(false);
        const busy = ref(false);

        let recognition = null;       // client
        let mediaRecorder = null;     // server
        let chunks = [];
        let stream = null;

        const speechApi = window.SpeechRecognition || window.webkitSpeechRecognition;
        const supported = computed(() =>
            props.mode === 'client'
                ? !!speechApi
                : !!(navigator.mediaDevices && window.MediaRecorder)
        );

        const label = computed(() => {
            if (busy.value) { return 'Verarbeite …'; }
            if (recording.value) { return 'Stopp'; }
            return 'Diktieren';
        });

        const buttonTitle = computed(() =>
            supported.value ? 'Per Sprache diktieren' : 'Diktieren wird von diesem Browser nicht unterstützt'
        );

        const applyText = (text) => {
            if (!text) { return; }
            const clean = text.trim();
            const next = props.appendMode && props.modelValue
                ? `${props.modelValue.trim()} ${clean}`.trim()
                : clean;
            emit('update:modelValue', next);
            emit('transcribed', clean);
        };

        // ---- Client-Modus: Web Speech API ----
        const startClient = () => {
            recognition = new speechApi();
            recognition.lang = props.language;
            recognition.continuous = true;
            recognition.interimResults = false;

            recognition.onresult = (event) => {
                let finalText = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        finalText += event.results[i][0].transcript;
                    }
                }
                applyText(finalText);
            };
            recognition.onerror = (e) => {
                emit('error', e.error || 'Spracherkennung fehlgeschlagen.');
                stopAll();
            };
            recognition.onend = () => { recording.value = false; };

            recognition.start();
            recording.value = true;
        };

        // ---- Server-Modus: aufnehmen + hochladen ----
        const startServer = async () => {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            chunks = [];
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) { chunks.push(e.data); } };
            mediaRecorder.onstop = uploadRecording;
            mediaRecorder.start();
            recording.value = true;
        };

        const uploadRecording = async () => {
            stopStream();
            if (chunks.length === 0) { recording.value = false; return; }

            busy.value = true;
            try {
                const blob = new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                const formData = new FormData();
                formData.append('audio', blob, 'aufnahme.webm');
                formData.append('language', props.language);

                const { data } = await window.axios.post(props.transcribeUrl, formData);
                applyText(data.text || '');
            } catch (e) {
                emit('error', 'Transkription fehlgeschlagen.');
            } finally {
                busy.value = false;
                recording.value = false;
            }
        };

        const stopStream = () => {
            if (stream) {
                stream.getTracks().forEach((t) => t.stop());
                stream = null;
            }
        };

        const stopAll = () => {
            if (recognition) { try { recognition.stop(); } catch (_) {} recognition = null; }
            if (mediaRecorder && mediaRecorder.state !== 'inactive') { mediaRecorder.stop(); }
            stopStream();
            recording.value = false;
        };

        const toggle = async () => {
            if (recording.value) { stopAll(); return; }
            try {
                if (props.mode === 'client') { startClient(); }
                else { await startServer(); }
            } catch (e) {
                emit('error', 'Mikrofonzugriff nicht möglich.');
                recording.value = false;
            }
        };

        onBeforeUnmount(stopAll);

        return { recording, busy, supported, label, buttonTitle, toggle };
    },
};
</script>
