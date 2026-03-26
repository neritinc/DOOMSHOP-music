import { defineStore } from "pinia";
import recommendationLinkService from "@/api/recommendationLinkService";

export const useMusicRecommendationsViewStore = defineStore("musicRecommendationsView", {
  state: () => ({
    recommendations: [],
    saving: false,
    formError: "",
    form: {
      title: "",
      media_url: "",
      sort_order: 0,
    },
  }),
  actions: {
    toEmbed(sourceUrl) {
      const raw = String(sourceUrl || "").trim();
      if (!raw) return { kind: "", embedUrl: "" };
      try {
        const url = new URL(raw);
        const host = (url.hostname || "").toLowerCase();
        if (host.includes("soundcloud.com")) {
          return {
            kind: "soundcloud",
            embedUrl: `https://w.soundcloud.com/player/?url=${encodeURIComponent(raw)}&color=%23ff7a18&auto_play=false&hide_related=false&show_comments=false&show_user=true&show_reposts=false&show_teaser=true&show_artwork=true&visual=false`,
          };
        }
        if (host.includes("youtu.be")) {
          const id = url.pathname.replace("/", "").trim();
          return { kind: "youtube", embedUrl: id ? `https://www.youtube.com/embed/${id}` : "" };
        }
        if (host.includes("youtube.com")) {
          if (url.pathname === "/watch") {
            const id = url.searchParams.get("v");
            return { kind: "youtube", embedUrl: id ? `https://www.youtube.com/embed/${id}` : "" };
          }
          if (url.pathname.startsWith("/shorts/")) {
            const id = url.pathname.split("/")[2] || "";
            return { kind: "youtube", embedUrl: id ? `https://www.youtube.com/embed/${id}` : "" };
          }
          if (url.pathname.startsWith("/embed/")) {
            return { kind: "youtube", embedUrl: raw };
          }
        }
      } catch (_err) {
      }
      return { kind: "", embedUrl: "" };
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
      const res = await recommendationLinkService.list();
      const rows = Array.isArray(res.data) ? res.data : [];
      this.recommendations = this.shuffle(rows.map((x) => {
        const embed = this.toEmbed(x.media_url);
        return {
          id: x.id,
          title: x.title,
          media_url: x.media_url,
          kind: embed.kind,
          embed_url: embed.embedUrl,
        };
      }).filter((x) => x.embed_url));
    },
    async addLink() {
      this.formError = "";
      this.saving = true;
      try {
        await recommendationLinkService.create({
          title: String(this.form.title || "").trim(),
          media_url: this.form.media_url,
          sort_order: Number(this.form.sort_order || 0),
        });
        this.form = { title: "", media_url: "", sort_order: 0 };
        await this.load();
      } catch (err) {
        this.formError = err?.response?.data?.message || "Failed to add recommendation.";
      } finally {
        this.saving = false;
      }
    },
    async removeLink(id) {
      await recommendationLinkService.destroy(id);
      await this.load();
    },
  },
});
