/**
 * Status Management Component
 *
 * CRUD interface for managing task statuses
 */

window.Vue.component('status-management', {
  template: `
    <div class="status-management">
      <app-header title="Status Management">
        <template #actions>
          <a href="/" class="btn-secondary">Back to Dashboard</a>
        </template>
      </app-header>

      <div class="status-content">
        <loader v-if="loading"></loader>
        <div v-else>
          <div class="status-form-section">
            <h2>{{ editingStatus ? 'Edit Status' : 'Create New Status' }}</h2>
            <form @submit.prevent="handleSubmit" class="status-form">
              <div class="form-row">
                <div class="form-group">
                  <label>Status Name *</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    placeholder="e.g., In Progress"
                  />
                  <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
                </div>

                <div class="form-group">
                  <label>Color *</label>
                  <div class="color-input-group">
                    <input
                      v-model="form.color"
                      type="color"
                      class="color-picker"
                    />
                    <input
                      v-model="form.color"
                      type="text"
                      class="form-control color-text"
                      placeholder="#FF5733"
                      pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                    />
                  </div>
                  <span v-if="errors.color" class="error-text">{{ errors.color[0] }}</span>
                </div>
              </div>

              <div v-if="errorMessage" class="error-message">
                {{ errorMessage }}
              </div>

              <div class="form-actions">
                <button
                  v-if="editingStatus"
                  type="button"
                  @click="cancelEdit"
                  class="btn-secondary"
                >
                  Cancel
                </button>
                <button type="submit" :disabled="submitting" class="btn-primary">
                  {{ submitting ? 'Saving...' : editingStatus ? 'Update Status' : 'Create Status' }}
                </button>
              </div>
            </form>
          </div>

          <div class="status-list-section">
            <h2>Existing Statuses</h2>
            <div class="status-list">
              <div
                v-for="status in statuses"
                :key="status.id"
                class="status-item"
              >
                <div class="status-info">
                  <div
                    class="status-color-badge"
                    :style="{ backgroundColor: status.color }"
                  ></div>
                  <span class="status-name">
                    {{ status.name }}
                    <span v-if="status.is_default" class="default-badge">Default</span>
                  </span>
                  <span class="status-color-code">{{ status.color }}</span>
                </div>
                <div class="status-actions">
                  <button
                    v-if="!status.is_default"
                    @click="setAsDefault(status.id)"
                    class="btn-icon"
                    title="Set as Default"
                  >
                    ⭐
                  </button>
                  <button @click="prepareForUpdating(status)" class="btn-icon" title="Edit">
                    ✏️
                  </button>
                  <button @click="deleteStatus(status.id)" class="btn-icon" title="Delete">
                    🗑️
                  </button>
                </div>
              </div>
              <div v-if="statuses.length === 0" class="empty-list">
                No statuses found
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirm Modal -->
      <confirm-modal
        :show="showDeleteConfirm"
        title="Delete Status"
        message="Are you sure you want to delete this status? This action cannot be undone."
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />

      <!-- Toast Messages -->
      <div v-if="toast.show" :class="['toast', toast.type]">
        {{ toast.message }}
      </div>
    </div>
  `,
  data() {
    return {
      statuses: [],
      loading: true,
      submitting: false,
      editingStatus: null,
      form: {
        name: '',
        color: '#667eea'
      },
      errors: {},
      errorMessage: '',
      showDeleteConfirm: false,
      statusToDelete: null,
      toast: {
        show: false,
        message: '',
        type: 'error' // 'success' or 'error'
      }
    };
  },
  async mounted() {
    await this.fetchStatuses();
  },
  methods: {
    async fetchStatuses() {
      this.loading = true;
      try {
        const response = await axios.get('/api/statuses');
        this.statuses = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching statuses:', error);
        this.errorMessage = 'Failed to load statuses';
      } finally {
        this.loading = false;
      }
    },
    async handleSubmit() {
      this.errors = {};
      this.errorMessage = '';
      this.submitting = true;

      try {
        let response;
        if (this.editingStatus) {
          // Update existing status
          response = await axios.put(`/api/statuses/${this.editingStatus.id}`, this.form);
          const updatedStatus = response.data.data;

          // Find and update the status in the array
          const index = this.statuses.findIndex(s => s.id === this.editingStatus.id);
          if (index !== -1) {
            this.$set(this.statuses, index, updatedStatus);
          }
        } else {
          // Create new status
          response = await axios.post('/api/statuses', this.form);
          const newStatus = response.data.data;

          // Add new status to the end of the array
          this.statuses.push(newStatus);
        }

        this.showToast(
          this.editingStatus ? 'Status updated successfully' : 'Status created successfully',
          'success'
        );
        this.resetForm();
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors || {};
        } else if (error.response && error.response.data.message) {
          this.showToast(error.response.data.message, 'error');
        } else {
          this.showToast('An error occurred. Please try again.', 'error');
        }
      } finally {
        this.submitting = false;
      }
    },
    prepareForUpdating(status) {
      this.editingStatus = status;
      this.form.name = status.name;
      this.form.color = status.color;
      this.errors = {};
      this.errorMessage = '';

      // Scroll to form
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    cancelEdit() {
      this.resetForm();
    },
    deleteStatus(statusId) {
      this.statusToDelete = statusId;
      this.showDeleteConfirm = true;
    },
    async confirmDelete() {
      this.showDeleteConfirm = false;

      try {
        await axios.delete(`/api/statuses/${this.statusToDelete}`);

        // Remove the status from the array
        const index = this.statuses.findIndex(s => s.id === this.statusToDelete);
        if (index !== -1) {
          this.statuses.splice(index, 1);
        }

        this.showToast('Status deleted successfully', 'success');
      } catch (error) {
        if (error.response && error.response.data.message) {
          this.showToast(error.response.data.message, 'error');
        } else {
          this.showToast('Failed to delete status', 'error');
        }
      } finally {
        this.statusToDelete = null;
      }
    },
    cancelDelete() {
      this.showDeleteConfirm = false;
      this.statusToDelete = null;
    },
    resetForm() {
      this.editingStatus = null;
      this.form = {
        name: '',
        color: '#667eea'
      };
      this.errors = {};
      this.errorMessage = '';
    },
    async setAsDefault(statusId) {
      try {
        await axios.put(`/api/statuses/${statusId}`, { is_default: true });

        // Update all statuses - set is_default to false for all, then true for the target
        this.statuses.forEach((status, index) => {
          if (status.id === statusId) {
            this.$set(this.statuses[index], 'is_default', true);
          } else if (status.is_default) {
            this.$set(this.statuses[index], 'is_default', false);
          }
        });

        this.showToast('Default status updated successfully', 'success');
      } catch (error) {
        if (error.response && error.response.data.message) {
          this.showToast(error.response.data.message, 'error');
        } else {
          this.showToast('Failed to set default status', 'error');
        }
      }
    },
    showToast(message, type = 'error') {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.show = true;

      setTimeout(() => {
        this.toast.show = false;
      }, 4000);
    }
  }
});

