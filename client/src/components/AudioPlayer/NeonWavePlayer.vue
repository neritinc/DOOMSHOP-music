<template>
  <section class="player-card">
    <audio ref="audioEl" :src="audioSrc" preload="metadata" @loadedmetadata="onLoadedMetadata" @timeupdate="onTimeUpdate" @ended="onEnded" @error="onError" />

    <div class="player-head">
      <p class="track-title">{{ props.track?.track_title || "Selected Track" }}</p>
    </div>

    <div class="row-main">
      <button class="btn-play" type="button" @click="togglePlay" :disabled="!audioSrc" aria-label="Play/Pause">
        <i v-if="!isPlaying" class="bi bi-play-fill icon"></i>
        <i v-else class="bi bi-pause-fill icon"></i>
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
          :style="{ '--progress': `${progressPercent}%` }"
        />

        <div class="times">
          <span class="time">{{ formatTime(currentTime) }}</span>
          <span class="time">-{{ formatTime(remainingTime) }}</span>
        </div>
      </div>

      <div class="volume-wrap">
        <input
          class="volumebar"
          type="range"
          min="0"
          max="1"
          step="0.01"
          :value="player.volume"
          @input="onVolume"
          :disabled="!audioSrc"
          aria-label="Volume"
          :style="{ '--vol': `${volumePercent}` }"
        />
        <button class="btn-mute" type="button" @click="toggleMute" :disabled="!audioSrc" aria-label="Mute/Unmute">
          <i v-if="isMuted" class="bi bi-volume-mute-fill icon-mute"></i>
          <i v-else class="bi bi-volume-up-fill icon-mute"></i>
        </button>
      </div>
    </div>

    <p v-if="loadError" class="error-text">A preview file nem toltheto be.</p>
  </section>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { usePlayerStore } from "@/stores/playerStore";

const props = defineProps({
  track: {
    type: Object,
    required: true,
  },
});

const player = usePlayerStore();
const audioEl = ref(null);
const isPlaying = computed(() => player.isPlaying);
const isMuted = ref(false);
const currentTime = ref(0);
const duration = ref(30);
const loadError = ref(false);

const apiBase = computed(() => import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api");

const trackId = computed(() => props.track?.id ?? props.track?.track_id ?? null);
const previewCacheKey = computed(() => {
  const pathPart = props.track?.preview_path || "";
  const start = props.track?.preview_start_at ?? "";
  const end = props.track?.preview_end_at ?? "";
  return encodeURIComponent(`${pathPart}|${start}|${end}`);
});
const audioSrc = computed(() =>
  trackId.value ? `${apiBase.value}/tracks/${trackId.value}/preview?v=${previewCacheKey.value}` : "",
);
const remainingTime = computed(() => Math.max(0, duration.value - currentTime.value));
const progressPercent = computed(() => {
  if (duration.value <= 0) return 0;
  return Math.max(0, Math.min(100, (currentTime.value / duration.value) * 100));
});
const volumePercent = computed(() => Math.round(player.volume * 100));

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

  if (player.isPlaying) {
    audioEl.value.pause();
    player.pause();
    return;
  }

  audioEl.value.play();
  player.play();
}

function toggleMute() {
  if (!audioEl.value) return;
  isMuted.value = !isMuted.value;
  audioEl.value.muted = isMuted.value;
}

function onVolume(event) {
  const next = Number(event.target.value || 0);
  player.setVolume(next);
  if (audioEl.value && isMuted.value && next > 0) {
    isMuted.value = false;
    audioEl.value.muted = false;
  }
}

function onEnded() {
  player.pause();
  currentTime.value = 0;
}

function onError() {
  player.pause();
  loadError.value = true;
}

function formatTime(value) {
  const total = Math.max(0, Math.floor(Number(value) || 0));
  const min = Math.floor(total / 60);
  const sec = total % 60;
  return `${min}:${String(sec).padStart(2, "0")}`;
}

watch(
  () => props.track,
  (track) => {
    player.setTrack(track ?? null, false);
    currentTime.value = 0;
    loadError.value = false;
  },
  { immediate: true },
);

watch(
  () => player.volume,
  (value) => {
    if (audioEl.value) audioEl.value.volume = value;
  },
  { immediate: true },
);
</script>

<style scoped>
.player-card {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(220px 160px at 2% -20%, rgba(245, 158, 11, 0.18), transparent 60%),
    radial-gradient(260px 180px at 98% -10%, rgba(14, 165, 233, 0.2), transparent 62%),
    linear-gradient(180deg, #ffffff 0%, #f4f9ff 100%);
  border-radius: 18px;
  border: 1px solid rgba(141, 179, 231, 0.38);
  box-shadow: 0 14px 30px rgba(41, 72, 126, 0.18);
  padding: 0.95rem 1rem 0.85rem;
}

.player-head {
  margin-bottom: 0.55rem;
}

.track-title {
  margin: 0;
  color: #112649;
  font-weight: 700;
  font-size: 0.96rem;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
  background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 55%, #4f46e5 100%);
  color: #fff;
  box-shadow: 0 10px 20px rgba(37, 99, 235, 0.35);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.btn-play:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.42);
}

.icon {
  font-size: 1.2rem;
  line-height: 1;
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
  background: linear-gradient(
    90deg,
    #3b82f6 0%,
    #60a5fa var(--progress),
    #cfe0fa var(--progress),
    #e3ecfb 100%
  );
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
  margin-top: 0.35rem;
  display: flex;
  justify-content: space-between;
}

.time {
  color: #1f3b66;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.btn-mute {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  border: 1px solid #bcd3fb;
  background: linear-gradient(180deg, #ffffff 0%, #eef5ff 100%);
  color: #1e40af;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4);
}

.icon-mute {
  font-size: 1rem;
}

.volume-wrap {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.volumebar {
  width: 90px;
  appearance: none;
  height: 6px;
  border-radius: 999px;
  background: linear-gradient(
    90deg,
    #38bdf8 0%,
    #60a5fa calc(var(--vol, 100) * 1%),
    #e3ecfb calc(var(--vol, 100) * 1%),
    #e3ecfb 100%
  );
  outline: none;
}

.volumebar::-webkit-slider-thumb {
  appearance: none;
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid #ffffff;
  background: #2563eb;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
}

.volumebar::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  border: 2px solid #ffffff;
  background: #2563eb;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
}

.error-text {
  margin: 0.5rem 0 0;
  color: #b91c1c;
  font-size: 0.85rem;
}

@media (max-width: 575px) {
  .track-title {
    white-space: normal;
  }

  .row-main {
    gap: 0.6rem;
  }

  .btn-play {
    width: 46px;
    height: 46px;
  }
}
</style>
