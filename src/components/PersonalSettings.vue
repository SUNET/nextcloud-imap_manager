<template>
  <div id="imap_manager" class="section">
    <div id="imap_manager_container" class="container">
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
      </NcSettingsSection>
    </div>
  </div>
</template>
<script>
import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
import NcPasswordField from "@nextcloud/vue/dist/Components/NcPasswordField.js";
import NcSettingsSection from "@nextcloud/vue/dist/Components/NcSettingsSection.js";
import NcTextField from "@nextcloud/vue/dist/Components/NcTextField.js";
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export default {
  name: "PersonalSettings",

  components: {
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
    };
  },
  methods: {
    async set() {
      var url = generateUrl("/apps/imap_manager/set");
      let params = { token: this.token, name: this.name };
      let result = await axios.post(url, params);
      if (result.data.status == "success") {
        console.log("New IMAP password set");
      }
    },
  },
  mounted() {},
};
</script>
