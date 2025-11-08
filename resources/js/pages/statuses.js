/**
 * Status Management Page
 */

// Load dependencies
require('../bootstrap');
window.Vue = require('vue');

// Load components
require('../components/loader');
require('../components/app-header');
require('../components/confirm-modal');
require('../components/status-management');

// Initialize Vue app
new window.Vue({
  el: '#app'
});

