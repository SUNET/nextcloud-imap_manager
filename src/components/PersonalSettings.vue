<template>
  <div id="imap_manager" class="container">
    <NcSettingsSection
      name="IMAP Manager"
      :icon="'imap'"
      description="(Re)set your IMAP password."
      @default="populate"
    >
      <div class="external-label">
        <label for="Password">Password</label>
        <NcPasswordField
          id="Password"
          :value.sync="token"
          :label-outside="true"
          placeholder="Enter your Secret"
        />
      </div>
      <div class="external-label">
        <label for="Name">Name</label>
        <NcTextField
          id="Name"
          :value.sync="name"
          :label-outside="true"
          placeholder="Enter name"
        />
      </div>
      <NcButton
        @click="(_) => set()"
        aria-label="Save"
        :disabled="disabled"
        :size="size"
        variant="primary"
      >
        <template>Save</template>
      </NcButton>
      <table id="imap-token-list" class="table" v-if="tokens.length > 0">
        <thead>
          <tr>
            <th>Name</th>
            <th>
              <span class="hidden-visually">
                {{ t("settings", "Actions") }}
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="token in tokens" :key="token.id">
            <td>{{ token.name }}</td>
            <td>
              <NcButton
                @click="delete(token.id)"
                aria-label="Delete"
                :disabled="disabled"
                :size="size"
                variant="tertiary"
              >
                <template #icon>
                  <Delete :size="20" />
                </template>
              </NcButton>
            </td>
          </tr>
        </tbody>
      </table>
    </NcSettingsSection>
  </div>
</template>
<script>
import Delete from "vue-material-design-icons/Delete.vue";
import NcActionButton from "@nextcloud/vue/dist/Components/NcActionButton.js";
import NcActions from "@nextcloud/vue/dist/Components/NcActions.js";
import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
import NcPasswordField from "@nextcloud/vue/dist/Components/NcPasswordField.js";
import NcSettingsSection from "@nextcloud/vue/dist/Components/NcSettingsSection.js";
import NcTextField from "@nextcloud/vue/dist/Components/NcTextField.js";
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export default {
  name: "PersonalSettings",

  components: {
    Delete,
    NcActionButton,
    NcActions,
    NcButton,
    NcPasswordField,
    NcSettingsSection,
    NcTextField,
  },

  props: [],
  data() {
    return {
      name: "",
      token: "",
      tokens: [],
    };
  },
  methods: {
    async delete(id) {
      var url = generateUrl("/apps/imap_manager/delete");
      let params = { id: id };
      let result = await axios.post(url, params);
      if (result.data.status == "success") {
        for (let token of this.tokens.entries()) {
          if (token.id == token_id) {
            this.tokens.splice(this.tokens.indexOf(token), 1);
            console.log("IMAP password deleted");
            break;
          }
        }
      }
    },
    async get() {
      var url = generateUrl("/apps/imap_manager/get");
      let result = await axios.get(url);
      if (result.data.success == true) {
        this.tokens = result.data.ids;
        console.log("IMAP passwords loaded");
      }
    },
    async set() {
      var url = generateUrl("/apps/imap_manager/set");
      let params = { token: this.token, name: this.name };
      let result = await axios.post(url, params);
      if (result.data.success == true) {
        console.log("New IMAP password set");
        this.tokens.push({
          id: result.data.id,
          token: this.token,
          name: this.name,
        });
        this.token = "";
        this.name = "";
      }
    },
  },
  mounted() {
    this.get().then(() => {});
  },
};
</script>
