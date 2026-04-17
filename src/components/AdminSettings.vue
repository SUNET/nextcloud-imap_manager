<template>
  <NcSettingsSection
    id="imap-manager-admin"
    name="IMAP Manager"
    description="Configure mail server backends for app password management."
  >
    <div class="wrapper">
      <p><strong>Backend Toggles</strong></p>
      <NcCheckboxRadioSwitch
        :checked="dovecotEnabled"
        @update:checked="dovecotEnabled = $event"
      >
        Enable Dovecot backend
      </NcCheckboxRadioSwitch>
      <NcCheckboxRadioSwitch
        :checked="stalwartEnabled"
        @update:checked="stalwartEnabled = $event"
      >
        Enable Stalwart backend
      </NcCheckboxRadioSwitch>
    </div>
    <div class="wrapper" v-if="stalwartEnabled">
      <p><strong>Stalwart Configuration</strong></p>
      <div class="external-label">
        <label for="StalwartUrl">Stalwart API URL</label>
        <NcTextField
          id="StalwartUrl"
          v-model="stalwartUrl"
          :label-outside="true"
          placeholder="https://mail.example.com/api"
        />
      </div>
      <div class="external-label">
        <label for="StalwartAdminUser">Admin Username</label>
        <NcTextField
          id="StalwartAdminUser"
          v-model="stalwartAdminUser"
          :label-outside="true"
          placeholder="admin"
        />
      </div>
      <div class="external-label">
        <label for="StalwartAdminPassword">Admin Password</label>
        <NcPasswordField
          id="StalwartAdminPassword"
          v-model="stalwartAdminPassword"
          :label-outside="true"
          placeholder="Admin Password"
        />
      </div>
      <div class="button-row">
        <NcButton @click="testConnection" variant="secondary">
          <template #icon>
            <Connection :size="20" />
          </template>
          Test Connection
        </NcButton>
      </div>
      <p v-if="testResult === 'success'" class="msg success">Connection successful</p>
      <p v-if="testResult === 'error'" class="msg error">Connection failed</p>
    </div>
    <div class="wrapper">
      <NcButton @click="save" variant="primary">
        <template #icon>
          <Check :size="20" />
        </template>
        Save
      </NcButton>
      <p v-if="saved" class="msg success">Settings saved</p>
    </div>
  </NcSettingsSection>
</template>

<script>
import Check from "vue-material-design-icons/Check.vue";
import Connection from "vue-material-design-icons/Connection.vue";

import {
  NcButton,
  NcCheckboxRadioSwitch,
  NcPasswordField,
  NcSettingsSection,
  NcTextField,
} from "@nextcloud/vue";

import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";
import {
  confirmPassword,
  isPasswordConfirmationRequired,
} from "@nextcloud/password-confirmation";
import "@nextcloud/password-confirmation/style.css";

export default {
  name: "AdminSettings",

  components: {
    Check,
    Connection,
    NcButton,
    NcCheckboxRadioSwitch,
    NcPasswordField,
    NcSettingsSection,
    NcTextField,
  },

  data() {
    return {
      dovecotEnabled: true,
      stalwartEnabled: false,
      stalwartUrl: "",
      stalwartAdminUser: "",
      stalwartAdminPassword: "",
      testResult: null,
      saved: false,
    };
  },

  mounted() {
    this.loadConfig();
  },

  methods: {
    async loadConfig() {
      const url = generateUrl("/apps/imap_manager/admin/config");
      const result = await axios.get(url);
      this.dovecotEnabled = result.data.dovecot_enabled;
      this.stalwartEnabled = result.data.stalwart_enabled;
      this.stalwartUrl = result.data.stalwart_url;
      this.stalwartAdminUser = result.data.stalwart_admin_user;
      this.stalwartAdminPassword = result.data.stalwart_admin_password;
    },

    async save() {
      const url = generateUrl("/apps/imap_manager/admin/config");
      if (isPasswordConfirmationRequired()) {
        await confirmPassword();
      }
      await axios.post(url, {
        dovecot_enabled: this.dovecotEnabled,
        stalwart_enabled: this.stalwartEnabled,
        stalwart_url: this.stalwartUrl,
        stalwart_admin_user: this.stalwartAdminUser,
        stalwart_admin_password: this.stalwartAdminPassword,
      });
      this.saved = true;
      setTimeout(() => { this.saved = false; }, 3000);
    },

    async testConnection() {
      this.testResult = null;
      const url = generateUrl("/apps/imap_manager/admin/test");
      try {
        const result = await axios.post(url);
        this.testResult = result.data.status === "success" ? "success" : "error";
      } catch (e) {
        this.testResult = "error";
      }
    },
  },
};
</script>

<style scoped>
.wrapper {
  margin-bottom: 16px;
}
.external-label {
  margin-bottom: 8px;
}
.button-row {
  margin-top: 8px;
  margin-bottom: 8px;
}
.msg {
  margin-top: 8px;
  font-weight: bold;
}
.msg.success {
  color: var(--color-success);
}
.msg.error {
  color: var(--color-error);
}
</style>
