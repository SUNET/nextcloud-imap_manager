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
        <template>Save</template>
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
              :label="App - password"
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
    <div class="wrapper">
      <p><strong>Sync settings</strong></p>
      <p class="settings-section__desc">Select the E-Mail provider you will be using as primary, the unchecked provider will be backup.</p>
      <form>
        <div id="select_m365">
          <input type="radio" id="m365" value="m365" v-model="radioValue" style="vertical-align: middle"/>
          <label for="m365">Microsoft 365</label>
        <div />
        <div id="select_sunet">
          <input type="radio" id="sunet" value="sunet" v-model="radioValue" style="vertical-align: middle" />
          <label for="sunet">Sunet Mail</label>
        <div />
        <div id="select_frequency">
          <select id="select_options" name="select_options" >
            <option id="daily" required value="daily" selected>Daily</option>
            <option id="hourly" required value="hourly">Hourly</option>
            <option id="minutely" required value="minutely">Every Minute</option>
          </select>
        </div>
        <div id="select_boxes">
          <input type="checkbox" id="calendar" v-model="calendarValue" style="vertical-align: middle" checked="{{calendarValue}}" required />
          <label for="calendar">Calendar</label>
          <input type="checkbox" id="contacts" v-model="contactsValue" style="vertical-align: middle" checked="{{contactsValue}}" required />
          <label for="contacts">Contacts</label>
          <input type="checkbox" id="email" v-model="emailValue" style="vertical-align: middle" checked="{{emailValue}}" />
          <label for="email">E-Mail</label>
        </div>
        <br />
        <NcButton
          v-model="syncActive"
          @click="set_sync()"
          aria-label="Save"
          :disabled="disabled"
          :size="size"
          variant="primary"
        >
          <template>Save</template>
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
import IconStar from 'vue-material-design-icons/Star.vue'
import IconStarOutline from 'vue-material-design-icons/StarOutline.vue'
import Key from "vue-material-design-icons/Key.vue";
import NcActionButton from "@nextcloud/vue/dist/Components/NcActionButton.js";
import NcActionRadio from "@nextcloud/vue/dist/Components/NcActionRadio.js";
import NcActions from "@nextcloud/vue/dist/Components/NcActions.js";
import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
import NcDialog from "@nextcloud/vue/dist/Components/NcDialog.js";
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
      calendarValue: true ,
      contactsValue: true ,
      emailValue: true,
      isCopied: false,
      name: "",
      showDialog: false,
      syncActive: false,
      token: "",
      tokens: [],
      radioValue: "m365",
      optionsValue: "daily",
      whatValue: []
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
      let csrf_url = generateUrl("/csrftoken");
      let csrfresult = await axios.get(csrf_url);
      console.log("CSRF token loaded");
      let csrftoken = csrfresult.data.token;
      let url = generateUrl(incoming_url);
      let result = await axios({
        method: method,
        url: url,
        data: payload,
        headers: { csrftoken },
      });
      return result;
    },
    async get() {
      let url = "/apps/imap_manager/get";
      let result = await this.csrfRequest(url, "GET");
      if ('ids' in result.data) {
        this.tokens = result.data.ids;
      }
      let values = result.data.values;
      console.log(values);
      if(values) {
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
        this.radioValue = values.source;
        this.syncActive = true;
      }
      console.log("IMAP passwords and sync settings loaded");
    },
    async set() {
      if (!this.name) {
        return;
      }
      this.token = uuidv4();
      let url = "/apps/imap_manager/set";

      let params = { token: this.token, name: this.name };
      let result = await this.csrfRequest(url, "POST", params);
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
      let url = "/apps/imap_manager/set_sync";
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
      var primary = this.radioValue;
      let params = {
        frequency: frequency,
        calendar: calendar,
        contacts: contacts,
        email: email,
        primary: primary 
      };
      console.log(params);
      let result = await this.csrfRequest(url, "POST", params);
      if (result.data.success == true) {
        console.log("New sync job set");
        this.syncActive = true;
      }
    },
    async unset(id) {
      let url = "/apps/imap_manager/delete";
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
