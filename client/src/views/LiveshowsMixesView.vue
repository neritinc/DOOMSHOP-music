<template>
  <section class="liveshows-page">
    <div class="header-box">
      <h2 class="mb-1">LIVESHOWS and Mixes</h2>
      <p class="mb-0">Official YouTube and SoundCloud links</p>
    </div>

    <form v-if="isAdmin" class="admin-add card card-body" @submit.prevent="addLink">
      <h3 class="admin-title">Add YouTube / SoundCloud link (Admin)</h3>
      <div v-if="formError" class="alert alert-danger py-2 mb-2">{{ formError }}</div>
      <div class="row g-2">
        <div class="col-md-10">
          <input v-model="form.youtube_url" class="form-control" placeholder="https://www.youtube.com/watch?v=... or https://soundcloud.com/..." required />
        </div>
        <div class="col-md-2">
          <input v-model.number="form.sort_order" class="form-control" type="number" min="0" max="127" placeholder="Order" />
        </div>
      </div>
      <button class="btn btn-primary btn-sm mt-2 align-self-start" :disabled="saving">
        {{ saving ? "Saving..." : "Add video" }}
      </button>
    </form>

    <div class="video-grid">
      <article v-for="video in videos" :key="video.id" class="video-card">
        <div class="video-head">
          <h3 class="video-title">{{ video.title }}</h3>
          <button
            v-if="isAdmin"
            type="button"
            class="btn btn-outline-danger btn-sm"
            @click="removeLink(video.id)"
          >
            Delete
          </button>
        </div>
        <div class="video-wrap">
          <iframe
            :src="video.embedUrl"
            :title="video.title"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
          ></iframe>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import { mapState } from "pinia";
import { useUserLoginLogoutStore } from "@/stores/userLoginLogoutStore";
import liveshowLinkService from "@/api/liveshowLinkService";

export default {
  data() {
    return {
      videos: [],
      saving: false,
      formError: "",
      form: {
        youtube_url: "",
        sort_order: 0,
      },
    };
  },
  computed: {
    ...mapState(useUserLoginLogoutStore, ["isAdmin"]),
  },
  methods: {
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
          return `https://w.soundcloud.com/player/?url=${encodeURIComponent(raw)}&color=%230f172a&auto_play=false&hide_related=false&show_comments=false&show_user=true&show_reposts=false&show_teaser=true&visual=true`;
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
      this.videos = this.shuffle(rows
        .map((x) => ({
          id: x.id,
          title: x.title,
          youtubeUrl: x.youtube_url,
          embedUrl: this.toEmbedUrl(x.youtube_url),
        }))
        .filter((x) => x.embedUrl));
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
  async mounted() {
    await this.load();
  },
};
</script>

<style scoped>
.liveshows-page {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.admin-add {
  border-radius: 14px;
  border: 1px solid #d8e5f7;
  background: #fff;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}

.admin-title {
  margin: 0 0 0.6rem;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.header-box {
  border-radius: 14px;
  padding: 1rem 1.1rem;
  border: 1px solid #d8e5f7;
  background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}

.video-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.video-card {
  border-radius: 14px;
  border: 1px solid #d8e5f7;
  background: #fff;
  padding: 0.8rem;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
}

.video-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
  margin-bottom: 0.55rem;
}

.video-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.video-wrap {
  position: relative;
  width: 100%;
  padding-top: 56.25%;
}

.video-wrap iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border-radius: 10px;
}

@media (max-width: 992px) {
  .video-grid {
    grid-template-columns: 1fr;
  }
}
</style>
