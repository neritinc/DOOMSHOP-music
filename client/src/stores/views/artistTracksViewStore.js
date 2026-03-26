import { defineStore } from "pinia";
import trackService from "@/api/trackService";
import artistService from "@/api/artistService";
import { storageUrl } from "@/utils/storageUrl";

export const useArtistTracksViewStore = defineStore("artistTracksView", {
  state: () => ({
    loading: true,
    error: "",
    artist: null,
    tracks: [],
  }),
  getters: {
    artistName(state) {
      return state.artist?.artist_name || "Artist Tracks";
    },
  },
  actions: {
    filteredTracks(artistId) {
      return this.tracks.filter((t) =>
        (t.artists || []).some((a) => Number(a.artist_id) === Number(artistId)),
      );
    },
    async load(artistId) {
      this.loading = true;
      this.error = "";
      try {
        const [artistRes, tracksRes] = await Promise.all([
          artistService.list(),
          trackService.list(),
        ]);
        const artists = artistRes.data || [];
        this.artist = artists.find((a) => Number(a.artist_id) === Number(artistId)) || null;
        this.tracks = tracksRes.data || [];
      } catch (err) {
        this.error = err?.response?.data?.message || "Loading failed.";
      } finally {
        this.loading = false;
      }
    },
    coverUrl(file) {
      if (!file) return "https://placehold.co/600x340?text=Track";
      if (String(file).startsWith("http://") || String(file).startsWith("https://")) return String(file);
      if (String(file).includes("/")) return storageUrl(String(file).replace(/^storage\//, ""));
      return storageUrl(`track-covers/${String(file).replace(/\\/g, "/").trim()}`);
    },
    onImgError(e) {
      e.target.src = "https://placehold.co/600x340?text=No+Cover";
    },
    trackId(track) {
      return track?.id ?? track?.track_id;
    },
    artistNames(track) {
      return (track.artists || []).map((a) => a.artist_name).join(", ") || "-";
    },
  },
});
