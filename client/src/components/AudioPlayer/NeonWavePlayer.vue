<template>
  <section class="player-card">
    <audio ref="audioEl" :src="audioSrc" preload="metadata" @loadedmetadata="onLoadedMetadata" @timeupdate="onTimeUpdate" @ended="onEnded" @error="onError" />

    <div class="row-main">
      <button class="btn-play" type="button" @click="togglePlay" :disabled="!audioSrc" aria-label="Play/Pause">
        <span v-if="!isPlaying" class="icon">▶</span>
        <span v-else class="icon">❚❚</span>
      </button>

      <div class="bar-wrap">
        <input
          class="seekbar"
          type="range"
          min="0"
          :max="duration"
          step="0.1"
          :value="currentTime"
          @input="onSeek"
          :disabled="!audioSrc || duration <= 0"
          aria-label="Seek preview"
        />

        <div class="times">
          <span class="time">{{ formatTime(currentTime) }}</span>
          <span class="time">-{{ formatTime(remainingTime) }}</span>
        </div>
      </div>

      <button class="btn-mute" type="button" @click="toggleMute" :disabled="!audioSrc" aria-label="Mute/Unmute">
        <span v-if="isMuted" class="icon-mute">🔇</span>
        <span v-else class="icon-mute">🔊</span>
      </button>
    </div>

    <p v-if="loadError" class="error-text">A preview file nem tölthető be.</p>
  </section>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  track: {
    type: Object,
    required: true,
  },
});

const audioEl = ref(null);
const isPlaying = ref(false);
const isMuted = ref(false);
const currentTime = ref(0);
const duration = ref(30);
const loadError = ref(false);

const apiBase = computed(() => import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api");

const trackId = computed(() => props.track?.id ?? props.track?.track_id ?? null);
const audioSrc = computed(() => (trackId.value ? `${apiBase.value}/tracks/${trackId.value}/preview` : ""));
const remainingTime = computed(() => Math.max(0, duration.value - currentTime.value));

function onLoadedMetadata() {
  const d = Number(audioEl.value?.duration || 30);
  duration.value = Number.isFinite(d) && d > 0 ? d : 30;
  loadError.value = false;
}

function onTimeUpdate() {
  currentTime.value = Number(audioEl.value?.currentTime || 0);
}

function onSeek(event) {
  const next = Number(event.target.value || 0);
  currentTime.value = next;
  if (audioEl.value) audioEl.value.currentTime = next;
}

function togglePlay() {
  if (!audioEl.value || !audioSrc.value) return;

  if (isPlaying.value) {
    audioEl.value.pause();
    isPlaying.value = false;
    return;
  }

  audioEl.value.play();
  isPlaying.value = true;
}

function toggleMute() {
  if (!audioEl.value) return;
  isMuted.value = !isMuted.value;
  audioEl.value.muted = isMuted.value;
}

function onEnded() {
  isPlaying.value = false;
  currentTime.value = 0;
}

function onError() {
  isPlaying.value = false;
  loadError.value = true;
}

function formatTime(value) {
  const total = Math.max(0, Math.floor(Number(value) || 0));
  const min = Math.floor(total / 60);
  const sec = total % 60;
  return `${min}:${String(sec).padStart(2, "0")}`;
}
</script>

<style scoped>
.player-card {
  background: linear-gradient(180deg, #f8fbff 0%, #f1f6ff 100%);
  border-radius: 16px;
  border: 1px solid #dbe7fb;
  box-shadow: 0 10px 26px rgba(70, 102, 156, 0.14);
  padding: 0.95rem 1rem;
}

.row-main {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.btn-play {
  width: 50px;
  height: 50px;
  border-radius: 999px;
  border: 0;
  background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
  color: #fff;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.32);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.btn-play:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.36);
}

.icon {
  font-size: 0.95rem;
  font-weight: 700;
}

.btn-play:disabled,
.btn-mute:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.bar-wrap {
  flex: 1;
  min-width: 0;
}

.seekbar {
  width: 100%;
  appearance: none;
  height: 8px;
  border-radius: 999px;
  background: linear-gradient(90deg, #bfdbfe 0%, #c7d2fe 100%);
  outline: none;
}

.seekbar::-webkit-slider-thumb {
  appearance: none;
  width: 17px;
  height: 17px;
  border-radius: 999px;
  border: 2px solid #ffffff;
  background: #2563eb;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
}

.seekbar::-moz-range-thumb {
  width: 17px;
  height: 17px;
  border-radius: 999px;
  border: 2px solid #ffffff;
  background: #2563eb;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
}

.times {
  margin-top: 0.4rem;
  display: flex;
  justify-content: space-between;
}

.time {
  color: #334155;
  font-size: 0.84rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.btn-mute {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  border: 1px solid #c9daf8;
  background: #ffffff;
  color: #1e3a8a;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4);
}

.icon-mute {
  font-size: 0.95rem;
}

.error-text {
  margin: 0.5rem 0 0;
  color: #b91c1c;
  font-size: 0.85rem;
}
</style>
