/**
 * Loader Component
 * 
 * Displays a loading spinner with optional message
 */

window.Vue.component('loader', {
  template: `
    <div class="loader-overlay">
      <div class="spinner"></div>
      <p>{{ message }}</p>
    </div>
  `,
  props: {
    message: {
      type: String,
      default: 'Loading...'
    }
  }
});

