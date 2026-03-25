import { defineStore } from "pinia";

export const usePlayerStore = defineStore("player", {
  state: () => ({
    currentTrack: null,
    isPlaying: false,
    volume: 1,
    queue: [],
    currentIndex: -1,
  }),
  getters: {
    hasTrack(state) {
      return state.currentTrack !== null;
    },
  },
  actions: {
    setTrack(track, autoplay = true) {
      this.currentTrack = track;
      this.isPlaying = Boolean(track) && autoplay;
    },
    play() {
      if (this.currentTrack) this.isPlaying = true;
    },
    pause() {
      this.isPlaying = false;
    },
    toggle() {
      if (this.currentTrack) this.isPlaying = !this.isPlaying;
    },
    setVolume(value) {
      const v = Number(value);
      if (Number.isNaN(v)) return;
      this.volume = Math.min(1, Math.max(0, v));
    },
    setQueue(tracks = [], startIndex = 0, autoplay = true) {
      this.queue = Array.isArray(tracks) ? tracks : [];
      if (this.queue.length === 0) {
        this.currentIndex = -1;
        this.currentTrack = null;
        this.isPlaying = false;
        return;
      }
      const idx = Math.min(
        Math.max(0, Number(startIndex) || 0),
        this.queue.length - 1,
      );
      this.currentIndex = idx;
      this.currentTrack = this.queue[idx];
      this.isPlaying = autoplay;
    },
    addToQueue(track) {
      if (!track) return;
      this.queue.push(track);
      if (!this.currentTrack) {
        this.currentIndex = 0;
        this.currentTrack = track;
      }
    },
    next() {
      if (this.queue.length === 0) return;
      const nextIndex = this.currentIndex + 1;
      if (nextIndex >= this.queue.length) {
        this.isPlaying = false;
        return;
      }
      this.currentIndex = nextIndex;
      this.currentTrack = this.queue[this.currentIndex];
      this.isPlaying = true;
    },
    prev() {
      if (this.queue.length === 0) return;
      const prevIndex = this.currentIndex - 1;
      if (prevIndex < 0) return;
      this.currentIndex = prevIndex;
      this.currentTrack = this.queue[this.currentIndex];
      this.isPlaying = true;
    },
    clearQueue() {
      this.queue = [];
      this.currentIndex = -1;
      this.currentTrack = null;
      this.isPlaying = false;
    },
  },
});
