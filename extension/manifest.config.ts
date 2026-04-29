import { defineManifest } from '@crxjs/vite-plugin';
import pkg from './package.json';

export default defineManifest({
  manifest_version: 3,
  name: 'aiPal',
  version: pkg.version,
  description: pkg.description,
  icons: {
    16: 'icons/icon-16.png',
    32: 'icons/icon-32.png',
    48: 'icons/icon-48.png',
    128: 'icons/icon-128.png',
  },
  action: {
    default_popup: 'src/popup/index.html',
    default_title: 'aiPal',
    default_icon: {
      16: 'icons/icon-16.png',
      32: 'icons/icon-32.png',
    },
  },
  options_page: 'src/options/index.html',
  background: {
    service_worker: 'src/background/index.ts',
    type: 'module',
  },
  permissions: ['activeTab', 'contextMenus', 'storage', 'scripting'],
  // No host_permissions: we rely on activeTab + scripting on user gesture only.
  commands: {
    _execute_action: {
      suggested_key: {
        default: 'Ctrl+Shift+A',
        mac: 'Command+Shift+A',
      },
      description: 'Open aiPal popup',
    },
  },
});
