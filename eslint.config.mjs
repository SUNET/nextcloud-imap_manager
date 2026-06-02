import globals from "globals";
import pluginJs from "@eslint/js";
import pluginVue from "eslint-plugin-vue";


/** @type {import('eslint').Linter.Config[]} */
export default [
  {files: ["**/*.{js,mjs,cjs,vue}"]},
  // t/n are Nextcloud-provided translation globals (registered via the Vue
  // mixin); OC/OCA are the Nextcloud client globals.
  {languageOptions: { globals: { ...globals.browser, t: "readonly", n: "readonly", OC: "readonly", OCA: "readonly" } }},
  pluginJs.configs.recommended,
  ...pluginVue.configs["flat/essential"],
];