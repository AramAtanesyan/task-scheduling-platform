/**
 * Login Page
 */

// Load dependencies
require('../bootstrap');
window.Vue = require('vue');

// Define login form component
window.Vue.component('login-form', {
  template: `
    <div class="login-container">
      <div class="login-card">
        <h2>Task Scheduling Platform</h2>
        <form @submit.prevent="handleLogin" class="login-form">
          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              placeholder="Enter your email"
              class="form-control"
            />
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              placeholder="Enter your password"
              class="form-control"
            />
          </div>
          <div v-if="error" class="error-message">{{ error }}</div>
          <button type="submit" :disabled="loading" class="btn-primary">
            {{ loading ? 'Logging in...' : 'Login' }}
          </button>
        </form>
      </div>
    </div>
  `,
  data() {
    return {
      email: '',
      password: '',
      error: '',
      loading: false
    };
  },
  methods: {
    async handleLogin() {
      this.error = '';
      this.loading = true;

      try {
        const response = await axios.post('/login', {
          email: this.email,
          password: this.password
        });

        if (response.data.success) {
          window.location.href = '/dashboard';
        } else {
          this.error = response.data.message || 'Login failed';
        }
      } catch (error) {
        if (error.response && error.response.data) {
          this.error = error.response.data.message || 'An error occurred during login';
        } else {
          this.error = 'An error occurred during login';
        }
      } finally {
        this.loading = false;
      }
    }
  }
});

// Initialize Vue app
new window.Vue({
  el: '#app'
});

