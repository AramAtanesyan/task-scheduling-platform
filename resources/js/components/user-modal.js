/**
 * User Modal Component
 *
 * Modal for creating new users
 */

window.Vue.component('user-modal', {
  template: `
    <div class="modal-overlay" @click.self="handleClose">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Create New User</h3>
          <button @click="handleClose" class="btn-close">×</button>
        </div>
        <form @submit.prevent="handleSubmit" class="modal-body">
          <div class="form-group">
            <label>Name *</label>
            <input v-model="form.name" type="text" class="form-control" />
            <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
          </div>

          <div class="form-group">
            <label>Email *</label>
            <input v-model="form.email" type="email" class="form-control" />
            <span v-if="errors.email" class="error-text">{{ errors.email[0] }}</span>
          </div>

          <div class="form-group">
            <label>Password *</label>
            <input v-model="form.password" type="password" class="form-control" />
            <span v-if="errors.password" class="error-text">{{ errors.password[0] }}</span>
          </div>

          <div class="form-group">
            <label>Role *</label>
            <select v-model="form.role" class="form-control">
              <option value="">Select a role</option>
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
            <span v-if="errors.role" class="error-text">{{ errors.role[0] }}</span>
          </div>

          <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
          </div>

          <div class="modal-footer">
            <button type="button" @click="handleClose" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="loading" class="btn-primary">
              {{ loading ? 'Creating...' : 'Create User' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  `,
  data() {
    return {
      form: {
        name: '',
        email: '',
        password: '',
        role: 'user'
      },
      errors: {},
      errorMessage: '',
      loading: false
    };
  },
  methods: {
    handleClose() {
      this.$emit('close');
    },
    async handleSubmit() {
      this.errors = {};
      this.errorMessage = '';
      this.loading = true;

      try {
        const response = await axios.post('/api/users', this.form);

        if (response.data.success) {
          this.$emit('save');
          // Reset form
          this.form = {
            name: '',
            email: '',
            password: '',
            role: 'user'
          };
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          if (error.response.data.errors) {
            this.errors = error.response.data.errors;
          }
        } else if (error.response && error.response.status === 403) {
          this.errorMessage = error.response.data.message || 'Unauthorized action.';
        } else {
          this.errorMessage = 'An error occurred. Please try again.';
        }
      } finally {
        this.loading = false;
      }
    }
  }
});

