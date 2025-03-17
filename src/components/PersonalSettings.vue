<template>
  <NcSettingsSection
    id="imap-manager"
    name="E-Mail Password Manager"
    :icon="'imap'"
    description="Generate app-password for IMAP."
    @default="populate"
  >
    <div class="wrapper">
      <NcTextField
        id="Name"
        label="Name"
        v-model:value="name"
        placeholder="Enter app-password name"
        style="width: 50%"
      />
      <br />
      <NcButton
        @click="set()"
        aria-label="Save"
        :disabled="disabled"
        :size="size"
        variant="primary"
      >
        <template>Save</template>
      </NcButton>
    </div>
    <NcDialog v-if="showDialog" name="App-password" :can-close="false">
      <template #actions>
        <NcButton
          @click="showDialog = false"
          aria-label="Cancel"
          variant="tertiary"
        >
          <template #icon>
            <Cancel :size="16" />
          </template>
        </NcButton>
        <NcButton @click="copy()" aria-label="Copy" variant="tertiary">
          <template #icon>
            <IconClipboard size="16" />
          </template>
        </NcButton>
      </template>
      <template #default>
        <div class="wrapper">
          <div>App-password will not be shown again.</div>
          <div id="app-password">
            <NcPasswordField
              id="token"
              v-model="newToken"
              :label="App - password"
            />
          </div>
        </div>
      </template>
    </NcDialog>
    <br />
    <div class="wrapper" v-if="tokens.length > 0">
      <div><strong>Issued app-passwords</strong></div>
      <br />
      <ul style="width: 50%">
        <NcListItem
          v-for="token in tokens"
          bold
          compact="true"
          name="token.name"
          oneline
        >
          <template #icon>
            <Key :size="16" />
          </template>
          <template #name>
            {{ token.name }}
          </template>
          <template #details>
            <NcButton
              @click="unset(token.id)"
              aria-label="Delete"
              variant="tertiary"
            >
              <template #icon>
                <Delete :size="16" />
              </template>
            </NcButton>
          </template>
        </NcListItem>
      </ul>
    </div>
  </NcSettingsSection>
</template>
<script>
import Delete from "vue-material-design-icons/Delete.vue";
import Cancel from "vue-material-design-icons/Cancel.vue";
import IconClipboard from "vue-material-design-icons/ContentCopy.vue";
import Key from "vue-material-design-icons/Key.vue";
import NcActionButton from "@nextcloud/vue/dist/Components/NcActionButton.js";
import NcActions from "@nextcloud/vue/dist/Components/NcActions.js";
import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
import NcDialog from "@nextcloud/vue/dist/Components/NcDialog.js";
import NcDialogButton from "@nextcloud/vue/dist/Components/NcDialogButton.js";
import NcListItem from "@nextcloud/vue/dist/Components/NcListItem.js";
import NcPasswordField from "@nextcloud/vue/dist/Components/NcPasswordField.js";
import NcSettingsSection from "@nextcloud/vue/dist/Components/NcSettingsSection.js";
import NcTextField from "@nextcloud/vue/dist/Components/NcTextField.js";
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";
import { showSuccess } from "@nextcloud/dialogs";
import { t } from "@nextcloud/l10n";
import { v4 as uuidv4 } from "uuid";

export default {
  name: "PersonalSettings",

  components: {
    Cancel,
    Delete,
    IconClipboard,
    Key,
    NcActionButton,
    NcActions,
    NcButton,
    NcDialog,
    NcListItem,
    NcPasswordField,
    NcSettingsSection,
    NcTextField,
  },

  props: [],
  data() {
    return {
      isCopied: false,
      name: "",
      newToken: "",
      showDialog: false,
      token: "",
      tokens: [],
    };
  },
  methods: {
    async copy() {
      try {
        await navigator.clipboard.writeText(this.newToken);
        showSuccess(t("imap_manager", "Token copied to the clipboard"));
        this.showDialog = false;
      } catch (e) {
        console.log(e);
        // no secure context or really old browser - need a fallback
        window.prompt(
          t(
            "imap_manager",
            "Clipboard not available. Please copy the token manually."
          ),
          this.reference
        );
      }
      this.isCopied = true;
      setTimeout(() => {
        this.isCopied = false;
      }, 2000);
    },
    async csrfRequest(incoming_url, method, payload) {
      var csrf_url = generateUrl("/csrftoken");
      let csrfresult = await axios.get(csrf_url);
      console.log("CSRF token loaded");
      let csrftoken = csrfresult.data.token;
      var url = generateUrl(incoming_url);
      let result = await axios({
        method: method,
        url: url,
        data: payload,
        headers: { csrftoken },
      });
      return result;
    },
    async get() {
      var url = "/apps/imap_manager/get";
      let result = await this.csrfRequest(url, "GET");
      if (result.data.success == true) {
        this.tokens = result.data.ids;
        console.log("IMAP passwords loaded");
      }
    },
    async set() {
      if (!this.name) {
        return;
      }
      this.newToken = uuidv4();
      var url = "/apps/imap_manager/set";

      let params = { token: this.token, name: this.name };
      let result = await this.csrfRequest(url, "POST", params);
      if (result.data.success == true) {
        console.log("New IMAP password set");
        this.token = this.newToken.replace(/./g, "*");
        this.showDialog = true;
        this.tokens.push({
          id: result.data.id,
          name: this.name,
        });
        this.name = "";
      }
    },
    async unset(id) {
      var url = "/apps/imap_manager/delete";
      let params = { id: id };
      let result = await this.csrfRequest(url, "POST", params);
      if (result.data.success == true) {
        for (var i = 0; i < this.tokens.length; i++) {
          var token = this.tokens[i];
          if (token.id == id) {
            this.tokens.splice(i, 1);
            console.log("IMAP password deleted");
            break;
          }
        }
      }
    },
  },
  mounted() {
    this.get().then(() => {});
  },
};
</script>
