<template>
  <NcSettingsSection
    id="imap-manager"
    name="E-Mail Password Manager"
    :icon="'imap'"
    description="Generate app-password for IMAP."
    @default="populate"
  >
    <div v-if="dovecotEnabled">
      <p><strong>Dovecot Password Manager</strong></p>
      <div class="wrapper">
        <NcTextField
          id="Name"
          label="Name"
          v-model="name"
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
          <template #default>Save</template>
        </NcButton>
      </div>
      <br />
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
                v-model="token"
                label="App-password"
              />
            </div>
          </div>
        </template>
      </NcDialog>
      <div class="wrapper" v-if="tokens.length > 0">
        <p><strong>Issued app-passwords</strong></p>
        <ul style="width: 50%">
          <NcListItem
            v-for="token in tokens"
            v-bind:key="token.id"
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
    </div>
    <div v-if="stalwartEnabled">
      <p><strong>Stalwart Password Manager</strong></p>
      <div class="wrapper">
        <NcTextField
          id="StalwartName"
          label="Name"
          v-model="stalwartName"
          placeholder="Enter app-password name"
          style="width: 50%"
        />
        <br />
        <NcButton
          @click="setStalwart()"
          aria-label="Save"
          variant="primary"
        >
          <template #default>Generate</template>
        </NcButton>
      </div>
      <br />
      <NcDialog v-if="showStalwartDialog" name="Stalwart App Password" :can-close="false">
        <template #actions>
          <NcButton
            @click="showStalwartDialog = false"
            aria-label="Cancel"
            variant="tertiary"
          >
            <template #icon>
              <Cancel :size="16" />
            </template>
          </NcButton>
          <NcButton @click="copyStalwart()" aria-label="Copy" variant="tertiary">
            <template #icon>
              <IconClipboard size="16" />
            </template>
          </NcButton>
        </template>
        <template #default>
          <div class="wrapper">
            <div>Save this password now — you can reveal it later, but it's easier to copy it now.</div>
            <div>
              <NcPasswordField
                id="stalwart-token"
                v-model="stalwartToken"
                label="App Password"
              />
            </div>
          </div>
        </template>
      </NcDialog>
      <div class="wrapper" v-if="stalwartPasswords.length > 0">
        <p><strong>Issued Stalwart passwords</strong></p>
        <ul style="width: 50%">
          <NcListItem
            v-for="pw in stalwartPasswords"
            v-bind:key="pw.name + pw.password"
            bold
            compact="true"
            :name="pw.name"
            oneline
          >
            <template #icon>
              <Key :size="16" />
            </template>
            <template #name>
              {{ pw.name }}
            </template>
            <template #subname>
              {{ pw.revealed ? pw.password : '••••••••' }}
            </template>
            <template #details>
              <NcButton
                @click="pw.revealed = !pw.revealed"
                aria-label="Reveal"
                variant="tertiary"
              >
                <template #icon>
                  <EyeOff v-if="pw.revealed" :size="16" />
                  <Eye v-else :size="16" />
                </template>
              </NcButton>
              <NcButton
                @click="deleteStalwart(pw.name, pw.password)"
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
    </div>
    <div v-if="!dovecotEnabled && !stalwartEnabled">
      <p>No mail backends configured. Contact your administrator.</p>
    </div>
    <div class="wrapper">
      <p><strong>Sync settings</strong></p>
      <p class="settings-section__desc">
        Select the E-Mail provider you will be using as primary, the unchecked
        provider will be backup.
      </p>
      <form>
        <div id="sync_enabled">
          <input
            type="checkbox"
            id="enabled"
            v-model="syncEnabled"
            style="vertical-align: middle"
            :checked="syncEnabled"
          />
          <label for="enbled">Enable sync</label>
        </div>
        <div id="select_m365">
          <input
            type="radio"
            id="m365"
            value="m365"
            v-model="radioValue"
            style="vertical-align: middle"
          />
          <label for="m365">Microsoft 365</label>
        </div>
        <div id="select_sunet">
          <input
            type="radio"
            id="sunet"
            value="sunet"
            v-model="radioValue"
            style="vertical-align: middle"
          />
          <label for="sunet">Sunet Mail</label>
        </div>
        <div id="select_frequency">
          <select id="select_options" name="select_options">
            <option id="daily" required value="daily" selected>Daily</option>
            <option id="hourly" required value="hourly">Hourly</option>
            <option id="minutely" required value="minutely">
              Every Minute
            </option>
          </select>
        </div>
        <div id="select_boxes">
          <input
            type="checkbox"
            id="calendar"
            v-model="calendarValue"
            style="vertical-align: middle"
            :checked="calendarValue"
            required
          />
          <label for="calendar">Calendar</label>
          <input
            type="checkbox"
            id="contacts"
            v-model="contactsValue"
            style="vertical-align: middle"
            :checked="contactsValue"
            required
          />
          <label for="contacts">Contacts</label>
          <input
            type="checkbox"
            id="email"
            v-model="emailValue"
            style="vertical-align: middle"
            :checked="emailValue"
          />
          <label for="email">E-Mail</label>
        </div>
        <br />
        <NcButton
          v-model="syncActive"
          @click="set_sync()"
          aria-label="Save"
          :size="size"
          variant="primary"
        >
          <template #default>Save</template>
          <template #icon>
            <IconStar v-if="syncActive" :size="20" />
            <IconStarOutline v-else :size="20" />
          </template>
        </NcButton>
      </form>
    </div>
  </NcSettingsSection>
</template>
<script>
import Cancel from "vue-material-design-icons/Cancel.vue";
import Delete from "vue-material-design-icons/Delete.vue";
import IconClipboard from "vue-material-design-icons/ContentCopy.vue";
import IconStar from "vue-material-design-icons/Star.vue";
import IconStarOutline from "vue-material-design-icons/StarOutline.vue";
import Eye from "vue-material-design-icons/Eye.vue";
import EyeOff from "vue-material-design-icons/EyeOff.vue";
import Key from "vue-material-design-icons/Key.vue";

import {
  NcActionButton,
  NcActionRadio,
  NcActions,
  NcButton,
  NcDialog,
  NcListItem,
  NcPasswordField,
  NcSettingsSection,
  NcTextField,
} from "@nextcloud/vue";

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
    Eye,
    EyeOff,
    IconClipboard,
    IconStar,
    IconStarOutline,
    Key,
    NcActionButton,
    NcActionRadio,
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
      calendarValue: true,
      contactsValue: true,
      emailValue: true,
      syncEnabled: false,
      isCopied: false,
      name: "",
      showDialog: false,
      syncActive: false,
      token: "",
      tokens: [],
      radioValue: "m365",
      optionsValue: "daily",
      whatValue: [],
      dovecotEnabled: true,
      stalwartEnabled: false,
      stalwartPasswords: [],
      stalwartName: "",
      stalwartToken: "",
      showStalwartDialog: false,
    };
  },
  methods: {
    async copy() {
      try {
        await navigator.clipboard.writeText(this.token);
        showSuccess(t("imap_manager", "Token copied to the clipboard"));
        this.showDialog = false;
      } catch (e) {
        console.log(e);
        // no secure context or really old browser - need a fallback
        window.prompt(
          t(
            "imap_manager",
            "Clipboard not available. Please copy the token manually.",
          ),
          this.reference,
        );
      }
      this.isCopied = true;
      setTimeout(() => {
        this.isCopied = false;
      }, 2000);
    },
    async get() {
      let url = generateUrl("/apps/imap_manager/get");
      let result = await axios.get(url);
      if ("ids" in result.data) {
        this.tokens = result.data.ids;
      }
      this.dovecotEnabled = result.data.dovecot_enabled ?? true;
      this.stalwartEnabled = result.data.stalwart_enabled ?? false;
      let values = result.data.values;
      console.log(values);
      if (values) {
        var selection = document.getElementById("select_options");
        for (var i = 0; i < selection.children.length; i++) {
          var option = selection.children[i];
          if (option.value == values.frequency) {
            selection.children[i].selected = true;
          } else {
            selection.children[i].selected = false;
          }
        }
        this.optionsValue = values.frequency;
        this.calendarValue = Boolean(values.calendar_enabled);
        this.contactsValue = Boolean(values.contacts_enabled);
        this.emailValue = Boolean(values.email_enabled);
        this.syncEnabled = Boolean(values.enabled);
        this.radioValue = values.source;
        this.syncActive = true;
        console.log("radioValue: " + this.radioValue + " " + values.source);
      }
      console.log("IMAP passwords and sync settings loaded");
      if (this.stalwartEnabled) {
        await this.loadStalwartPasswords();
      }
    },
    async set() {
      if (!this.name) {
        return;
      }
      this.token = uuidv4();
      let url = generateUrl("/apps/imap_manager/set");

      let params = { token: this.token, name: this.name };
      let result = await axios.post(url, params);
      if (result.data.success == true) {
        console.log("New IMAP password set");
        this.showDialog = true;
        this.tokens.push({
          id: result.data.id,
          name: this.name,
        });
        this.name = "";
      }
    },
    async set_sync() {
      // TODO: Implement delete of sync job
      let url = generateUrl("/apps/imap_manager/set_sync");
      var selection = document.getElementById("select_options");
      var frequency = this.optionsValue;
      for (var i = 0; i < selection.children.length; i++) {
        var option = selection.children[i];
        if (option.selected) {
          frequency = option.id;
          this.optionsValue = option.id;
          break;
        }
      }
      var calendar = Boolean(this.calendarValue);
      var contacts = Boolean(this.contactsValue);
      var email = Boolean(this.emailValue);
      var enabled = Boolean(this.syncEnabled);
      var primary = this.radioValue;
      let params = {
        frequency: frequency,
        calendar: calendar,
        contacts: contacts,
        email: email,
        enabled: enabled,
        primary: primary,
      };
      console.log(params);
      let result = await axios.post(url, params);
      if (result.data.success == true) {
        console.log("New sync job set");
        if (this.syncActive) {
          this.syncActive = false;
          setTimeout(() => {
            this.syncActive = true;
          }, 300);
        } else {
          this.syncActive = true;
        }
      }
    },
    async unset(id) {
      let url = generateUrl("/apps/imap_manager/delete");
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
    async loadStalwartPasswords() {
      let url = generateUrl("/apps/imap_manager/stalwart/get");
      let result = await axios.get(url);
      if (result.data.success) {
        this.stalwartPasswords = result.data.passwords.map((p) => ({
          ...p,
          revealed: false,
        }));
      }
    },
    async setStalwart() {
      if (!this.stalwartName) {
        return;
      }
      this.stalwartToken = uuidv4();
      let url = generateUrl("/apps/imap_manager/stalwart/set");
      let params = { name: this.stalwartName, password: this.stalwartToken };
      let result = await axios.post(url, params);
      if (result.data.success) {
        this.showStalwartDialog = true;
        this.stalwartPasswords.push({
          name: this.stalwartName,
          password: this.stalwartToken,
          revealed: false,
        });
        this.stalwartName = "";
      }
    },
    async deleteStalwart(name, password) {
      let url = generateUrl("/apps/imap_manager/stalwart/delete");
      let params = { name: name, password: password };
      let result = await axios.post(url, params);
      if (result.data.success) {
        this.stalwartPasswords = this.stalwartPasswords.filter(
          (p) => !(p.name === name && p.password === password)
        );
      }
    },
    async copyStalwart() {
      try {
        await navigator.clipboard.writeText(this.stalwartToken);
        showSuccess(t("imap_manager", "Password copied to the clipboard"));
        this.showStalwartDialog = false;
      } catch (e) {
        window.prompt(
          t("imap_manager", "Clipboard not available. Please copy the password manually."),
          this.stalwartToken,
        );
      }
    },
  },
  mounted() {
    this.get().then(() => {});
  },
};
</script>
