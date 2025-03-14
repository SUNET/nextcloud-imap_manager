<template>
  <NcSettingsSection
    id="imap-manager"
    name="IMAP Manager"
    :icon="'imap'"
    description="Generate app-password for IMAP."
    @default="populate"
  >
    <div class="external-label">
      <label for="Name">Name</label>
      <NcTextField
        id="Name"
        v-model:value="name"
        :label-outside="true"
        placeholder="Enter name"
      />
    </div>
    <div>
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
    <NcDialog v-if="showDialog" name="token" :can-close="false">
      <template #actions>
        <NcButton @click="copy()" aria-label="Copy" variant="tertiary">
          <template #icon>
            <IconClipboard size="16" />
          </template>
        </NcButton>
      </template>
      <div>
        {{ this.token }}
        <NcButton
          @click="toggle()"
          aria-label="Toggle visibility"
          :disabled="disabled"
          :size="size"
          variant="tertiary"
        >
          <Eye :size="16" />
        </NcButton>
      </div>
    </NcDialog>
    <ul>
      <NcListItem v-for="token in tokens" bold name="token.name" oneline>
        <template #name>
          {{ token.name }}
        </template>
        <template #description>
          {{ token.name }}
        </template>
        <template #actions>
          <NcActions>
            <NcActionButton
              @click="unset(token.id)"
              aria-label="Delete"
              :disabled="disabled"
              :size="size"
            >
              <template #icon>
                <Delete :size="16" />
              </template>
            </NcActionButton>
          </NcActions>
        </template>
      </NcListItem>
    </ul>
  </NcSettingsSection>
</template>
<script>
import Delete from "vue-material-design-icons/Delete.vue";
import Eye from "vue-material-design-icons/Eye.vue";
import IconClipboard from "vue-material-design-icons/ContentCopy.vue";
import NcActionButton from "@nextcloud/vue/dist/Components/NcActionButton.js";
import NcActions from "@nextcloud/vue/dist/Components/NcActions.js";
import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
import NcDialog from "@nextcloud/vue/dist/Components/NcDialog.js";
import NcListItem from "@nextcloud/vue/dist/Components/NcListItem.js";
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
    Delete,
    Eye,
    IconClipboard,
    NcActionButton,
    NcActions,
    NcButton,
    NcDialog,
    NcListItem,
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
      showToken: false,
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
    async get() {
      var url = generateUrl("/apps/imap_manager/get");
      let result = await axios.get(url);
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
      var url = generateUrl("/apps/imap_manager/set");

      let params = { token: this.token, name: this.name };
      let result = await axios.post(url, params);
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
    async toggle() {
      if (this.showToken == true) {
        this.showToken = false;
        this.token = this.token.replace(/./g, "*");
      } else {
        this.showToken = true;
        this.token = this.newToken;
      }
    },
    async unset(id) {
      var url = generateUrl("/apps/imap_manager/delete");
      let params = { id: id };
      let result = await axios.post(url, params);
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
