<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 m-0">Music Recommendations</h2>
      <span class="badge text-bg-dark">{{ recommendations.length }} items</span>
    </div>

    <div class="row g-3">
      <div class="col-12" v-for="item in recommendations" :key="item.soundcloud_url">
        <article class="card h-100 shadow-sm reco-card">
          <div class="card-body">
            <div class="embed-wrap">
              <iframe
                class="sc-iframe"
                :src="embedUrl(item.soundcloud_url)"
                title="SoundCloud Player"
                scrolling="no"
                frameborder="no"
                allow="autoplay"
              ></iframe>
            </div>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    recommendations() {
      if (Array.isArray(this.items) && this.items.length > 0) {
        return this.items;
      }

      return [
        {
          title: "THE23ISHERE",
          artist: "Rooler",
          soundcloud_url: "https://soundcloud.com/roolerofficial/the23ishere",
        },
        {
          title: "Hard Beat",
          artist: "TNT x Darren Styles",
          soundcloud_url: "https://soundcloud.com/officialrevelationnl/tnt-darren-styles-hard-beat",
        },
        {
          title: "Griztronics",
          artist: "Official Cyclops",
          soundcloud_url: "https://soundcloud.com/officialcyclops/griztronics",
        },
        {
          title: "I Like The Noise (DnB Edit)",
          artist: "USED",
          soundcloud_url: "https://soundcloud.com/usedmusicbe/i-like-the-noise-used-dnb-edit",
        },
      ];
    },
  },
  methods: {
    embedUrl(soundcloudUrl) {
      const encoded = encodeURIComponent(soundcloudUrl || "");
      return `https://w.soundcloud.com/player/?url=${encoded}&color=%232563eb&auto_play=false&hide_related=false&show_comments=false&show_user=true&show_reposts=false&show_teaser=true`;
    },
  },
};
</script>

<style scoped>
.reco-card {
  border: 1px solid #d9dee5;
}

.embed-wrap {
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid #dbeafe;
  background: #eff6ff;
}

.sc-iframe {
  display: block;
  width: 100%;
  height: 170px;
}
</style>
