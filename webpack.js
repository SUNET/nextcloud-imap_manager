// SPDX-FileCopyrightText: Pondersource <michiel@pondersource.com>
// SPDX-License-Identifier: AGPL-3.0-or-later
const path = require("path");
const webpackConfig = require("@nextcloud/webpack-vue-config");
const appId = "imap_manager";
webpackConfig.entry = {
  personalSettings: {
    import: path.join(__dirname, "src", "personalSettings.js"),
    filename: appId + "-personalSettings.js",
  },
};
module.exports = webpackConfig;
