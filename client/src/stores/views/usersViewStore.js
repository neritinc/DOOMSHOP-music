import { defineStore } from "pinia";
import { useUserStore } from "@/stores/userStore";
import { useSearchStore } from "@/stores/searchStore";
import { useToastStore } from "@/stores/toastStore";

export const useUsersViewStore = defineStore("usersView", {
  state: () => ({
    pageTitle: "Users",
    tableColumns: [
      { key: "id", label: "ID", debug: import.meta.env.VITE_DEBUG_MODE },
      { key: "name", label: "User Name", debug: 2 },
      { key: "email", label: "Email", debug: 2 },
      { key: "role", label: "Role", debug: 2 },
    ],
    useCollectionStore: useUserStore,
    isOpenConfirmModal: false,
    toDeleteId: null,
    state: "r",
    title: "",
    refs: {},
  }),
  actions: {
    bindRefs(refs) {
      this.refs = { ...this.refs, ...refs };
    },
    deleteHandler(id) {
      this.state = "d";
      this.isOpenConfirmModal = true;
      this.toDeleteId = id;
    },
    async updateHandler(id) {
      const userStore = useUserStore();
      this.state = "u";
      this.title = "Edit User";
      await userStore.getById(id);
      this.refs?.form?.value?.show?.();
    },
    createHandler() {
      const toast = useToastStore();
      toast.messages.push("Users cannot be created from this page.");
      toast.show("Error");
    },
    passwordChangeHandler(_id) {
    },
    async sortHandler(column) {
      const userStore = useUserStore();
      await userStore.getAllSortSearch(column);
    },
    cancelHandler() {
      this.isOpenConfirmModal = false;
      this.state = "r";
    },
    async confirmHandler() {
      const userStore = useUserStore();
      try {
        await userStore.delete(this.toDeleteId);
      } catch (_error) {
      }
      this.isOpenConfirmModal = false;
      this.state = "r";
    },
    async yesEventFormHandler({ item, done }) {
      const userStore = useUserStore();
      try {
        if (this.state === "c") {
          await userStore.create(item);
        } else {
          await userStore.update(item.id, item);
        }
        this.state = "r";
        done(true);
      } catch (err) {
        if (err.response && err.response.status === 422) {
          this.refs?.form?.value?.setServerErrors?.(err.response.data.errors);
          done(false);
        } else {
          done(false);
        }
      }
    },
    async handleSearchChanged() {
      const userStore = useUserStore();
      await userStore.getAllSortSearch(userStore.sortColumn, userStore.sortDirection);
    },
    async load() {
      const searchStore = useSearchStore();
      const userStore = useUserStore();
      searchStore.resetSearchWord();
      await userStore.getAll();
    },
  },
});
