import { defineStore } from "pinia";

export const useSearchStore = defineStore("search", {
  state: () => ({
    searchWord: "",
  }),
  actions: {
    setSearchWord(value) {
      this.searchWord = value;
    },
    resetSearchWord() {
      this.searchWord = "";
    },
  },
});
