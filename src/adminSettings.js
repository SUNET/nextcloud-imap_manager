/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Micke Nordin <kano@sunet.se>
 * @copyright Micke Nordin 2025
 */

import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

const app = createApp(AdminSettings)

app.mixin({
  methods: {
    t,
    n
  }
})

app.mount('#imap_manager_admin')
