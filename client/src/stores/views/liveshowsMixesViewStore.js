import { defineStore } from "pinia";
import liveshowLinkService from "@/api/liveshowLinkService";

export const useLiveshowsMixesViewStore = defineStore("liveshowsMixesView", {
  state: () => ({
    videos: [],
    saving: false,
    formError: "",
    form: {
      youtube_url: "",
      sort_order: 0,
    },
  }),
  actions: {
    toEmbedUrl(sourceUrl) {
      const raw = String(sourceUrl || "").trim();
      if (!raw) return "";
      try {
        const url = new URL(raw);
        const host = (url.hostname || "").toLowerCase();
        if (host.includes("youtu.be")) {
          const id = url.pathname.replace("/", "").trim();
          return id ? `https://www.youtube.com/embed/${id}` : "";
        }
        if (host.includes("youtube.com")) {
          if (url.pathname === "/watch") {
            const id = url.searchParams.get("v");
            return id ? `https://www.youtube.com/embed/${id}` : "";
          }
          if (url.pathname.startsWith("/shorts/")) {
            const id = url.pathname.split("/")[2] || "";
            return id ? `https://www.youtube.com/embed/${id}` : "";
          }
          if (url.pathname.startsWith("/embed/")) {
            return raw;
          }
        }
        if (host.includes("soundcloud.com")) {
          return `https://w.soundcloud.com/player/?url=${encodeURIComponent(raw)}&color=%23ff7a18&auto_play=false&hide_related=false&show_comments=false&show_user=true&show_reposts=false&show_teaser=true&show_artwork=true&visual=false`;
        }
      } catch (_err) {
      }
      return "";
    },
    shuffle(items) {
      const out = [...items];
      for (let i = out.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [out[i], out[j]] = [out[j], out[i]];
      }
      return out;
    },
    async load() {
      const res = await liveshowLinkService.list();
      const rows = Array.isArray(res.data) ? res.data : [];
      this.videos = this.shuffle(rows.map((x) => ({
        id: x.id,
        title: x.title,
        youtubeUrl: x.youtube_url,
        embedUrl: this.toEmbedUrl(x.youtube_url),
      })).filter((x) => x.embedUrl));
    },
    async addLink() {
      this.formError = "";
      this.saving = true;
      try {
        await liveshowLinkService.create({
          youtube_url: this.form.youtube_url,
          sort_order: Number(this.form.sort_order || 0),
        });
        this.form = { youtube_url: "", sort_order: 0 };
        await this.load();
      } catch (err) {
        this.formError = err?.response?.data?.message || "Failed to add link.";
      } finally {
        this.saving = false;
      }
    },
    async removeLink(id) {
      await liveshowLinkService.destroy(id);
      await this.load();
    },
  },
});
