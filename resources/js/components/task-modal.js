/**
 * Task Modal Component
 *
 * Modal for creating and editing tasks
 */

window.Vue.component('task-modal', {
  template: `
    <div class="modal-overlay" @click.self="handleClose">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ modalTitle }}</h3>
          <button @click="handleClose" class="btn-close">×</button>
        </div>
        <form @submit.prevent="handleSubmit" class="modal-body">

          <div class="form-group">
            <label>Title *</label>
            <input v-model="form.title" type="text" class="form-control" :disabled="editingTask && !isAdmin" />
            <div v-if="errors.title" class="error-message">{{ errors.title[0] }}</div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea v-model="form.description" class="form-control" rows="3" :disabled="editingTask && !isAdmin"></textarea>
            <div v-if="errors.description" class="error-message">{{ errors.description[0] }}</div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Start Date *</label>
              <input v-model="form.start_date" type="date" class="form-control" :disabled="editingTask && !isAdmin" />
              <div v-if="errors.start_date" class="error-message">{{ errors.start_date[0] }}</div>
            </div>

            <div class="form-group">
              <label>End Date *</label>
              <input v-model="form.end_date" type="date" class="form-control" :disabled="editingTask && !isAdmin" />
              <div v-if="errors.end_date" class="error-message">{{ errors.end_date[0] }}</div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Assigned User *</label>
              <select v-model="form.user_id" class="form-control" :disabled="editingTask && !isAdmin">
                <option value="">Select a user</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
              <div v-if="errors.user_id" class="error-message">{{ errors.user_id[0] }}</div>
            </div>

            <div class="form-group">
              <label>Status *</label>
              <select v-model="form.status_id" class="form-control" :disabled="editingTask && !canEdit">
                <option value="">Select a status</option>
                <option v-for="status in statuses" :key="status.id" :value="status.id">
                  {{ status.name }}
                </option>
              </select>
              <div v-if="errors.status_id" class="error-message">{{ errors.status_id[0] }}</div>
            </div>
          </div>

          <div v-if="generalError && !hasFieldErrors" class="error-message">
            {{ generalError }}
          </div>

          <div v-if="!editingTask || canEdit" class="modal-footer">
            <button type="button" @click="handleClose" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="loading" class="btn-primary">
              {{ loading ? 'Saving...' : editingTask ? 'Update' : 'Create' }}
            </button>
          </div>
          <div v-else class="modal-footer">
            <button type="button" @click="handleClose" class="btn-primary">Close</button>
          </div>
        </form>
      </div>
    </div>
  `,
  props: {
    task: {
      type: Object,
      default: null
    },
    users: {
      type: Array,
      required: true
    },
    statuses: {
      type: Array,
      required: true
    },
    currentUser: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      form: {
        title: '',
        description: '',
        start_date: '',
        end_date: '',
        user_id: '',
        status_id: ''
      },
      errors: {},
      generalError: '',
      loading: false
    };
  },
  computed: {
    editingTask() {
      return this.task;
    },
    isAdmin() {
      return this.currentUser && this.currentUser.role === 'admin';
    },
    isOwnTask() {
      return this.task && this.currentUser && this.task.user_id === this.currentUser.id;
    },
    canEdit() {
      return this.isAdmin || this.isOwnTask;
    },
    hasFieldErrors() {
      return Object.keys(this.errors).length > 0;
    },
    modalTitle() {
      if (!this.editingTask) {
        return 'Create Task';
      }
      return this.canEdit ? 'Edit Task' : 'View Task';
    }
  },
  watch: {
    task: {
      immediate: true,
      handler(newTask) {
        if (newTask) {
          this.form = {
            title: newTask.title,
            description: newTask.description || '',
            start_date: this.formatDateForInput(newTask.start_date),
            end_date: this.formatDateForInput(newTask.end_date),
            user_id: newTask.user_id,
            status_id: newTask.status_id
          };
        } else {
          // Set default status for new tasks
          const defaultStatus = this.statuses.find(status => status.is_default);
          this.form = {
            title: '',
            description: '',
            start_date: '',
            end_date: '',
            user_id: '',
            status_id: defaultStatus ? defaultStatus.id : ''
          };
        }
        this.errors = {};
        this.generalError = '';
      }
    }
  },
  methods: {
    formatDateForInput(date) {
      if (!date) return '';
      // Handle both date strings and date objects
      const d = new Date(date);
      if (isNaN(d.getTime())) return '';
      // Format as YYYY-MM-DD for HTML date input
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    handleClose() {
      this.$emit('close');
    },
    async handleSubmit() {
      // Prevent submission if user can't edit the task
      if (this.editingTask && !this.canEdit) {
        this.generalError = 'You can only edit your own tasks.';
        return;
      }

      this.errors = {};
      this.generalError = '';
      this.loading = true;

      try {
        const url = this.editingTask
          ? `/api/tasks/${this.editingTask.id}`
          : '/api/tasks';
        const method = this.editingTask ? 'put' : 'post';

        // For non-admin users editing tasks, only send status_id
        const payload = (this.editingTask && !this.isAdmin)
          ? { status_id: this.form.status_id }
          : this.form;

        const response = await axios[method](url, payload);

        if (response.data.success) {
          this.$emit('save');
        }
      } catch (error) {
        if (error.response && error.response.data) {
          // Check for field-specific validation errors
          if (error.response.data.errors) {
            this.errors = error.response.data.errors;
          }
          // Check for general error message (like availability conflicts)
          if (error.response.data.message) {
            this.generalError = error.response.data.message;
          }
        }
      } finally {
        this.loading = false;
      }
    }
  }
});

