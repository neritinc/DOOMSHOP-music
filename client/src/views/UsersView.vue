<template>
  <div>
    <div class="d-flex align-items-center m-0 mb-2">
      <h1>{{ pageTitle }}</h1>
      <div class="d-flex align-items-center m-0 ms-2">
        <i
          v-if="loading"
          class="bi bi-hourglass-split fs-3 col-auto p-0 pe-1"
        ></i>
        <p class="m-0 ms-2">({{ getItemsLength }})</p>
      </div>
    </div>

    <GenericTable
      :items="items"
      :columns="tableColumns"
      :useCollectionStore="useCollectionStore"
      :cButtonVisible="false"
      :pButtonVisible="true"
      @delete="deleteHandler"
      @update="updateHandler"
      @create="createHandler"
      @passwordChange="passwordChangeHandler"
      @sort="sortHandler"
      v-if="items.length > 0"
    />
    <div v-else style="width: 100px" class="m-auto">No results</div>

    <FormUser
      ref="form"
      :title="title"
      :item="item"
      @yesEventForm="yesEventFormHandler"
    />

    <ConfirmModal
      :isOpenConfirmModal="isOpenConfirmModal"
      @cancel="cancelHandler"
      @confirm="confirmHandler"
    />
  </div>
</template>

<script>
import { ref, watch, onMounted } from "vue";
import { storeToRefs } from "pinia";
import { useUserStore } from "@/stores/userStore";
import { useSearchStore } from "@/stores/searchStore";
import { useUsersViewStore } from "@/stores/views/usersViewStore";
import GenericTable from "@/components/Table/GenericTable.vue";
import ConfirmModal from "@/components/Confirm/ConfirmModal.vue";
import FormUser from "@/components/Forms/FormUser.vue";

export default {
  name: "UsersView",
  components: {
    GenericTable,
    ConfirmModal,
    FormUser,
  },
  setup() {
    const viewStore = useUsersViewStore();
    const userStore = useUserStore();
    const searchStore = useSearchStore();
    const form = ref(null);

    viewStore.bindRefs({ form });

    const userRefs = storeToRefs(userStore);
    const searchRefs = storeToRefs(searchStore);
    const viewRefs = storeToRefs(viewStore);

    watch(searchRefs.searchWord, async () => {
      await viewStore.handleSearchChanged();
    });

    onMounted(async () => {
      await viewStore.load();
    });

    return {
      ...userRefs,
      ...searchRefs,
      ...viewRefs,
      form,
      deleteHandler: viewStore.deleteHandler,
      updateHandler: viewStore.updateHandler,
      createHandler: viewStore.createHandler,
      passwordChangeHandler: viewStore.passwordChangeHandler,
      sortHandler: viewStore.sortHandler,
      cancelHandler: viewStore.cancelHandler,
      confirmHandler: viewStore.confirmHandler,
      yesEventFormHandler: viewStore.yesEventFormHandler,
    };
  },
};
</script>

<style></style>
