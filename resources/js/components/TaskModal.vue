<template>
  <div class="modal-overlay" @click.self="handleClose">
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ editingTask ? 'Edit Task' : 'Create Task' }}</h3>
        <button @click="handleClose" class="btn-close">×</button>
      </div>
      <form @submit.prevent="handleSubmit" class="modal-body">
        <div class="form-group">
          <label>Title *</label>
          <input v-model="form.title" type="text" required class="form-control" />
          <span v-if="errors.title" class="error-text">{{ errors.title }}</span>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea v-model="form.description" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Start Date *</label>
            <input v-model="form.start_date" type="date" required class="form-control" />
            <span v-if="errors.start_date" class="error-text">{{ errors.start_date }}</span>
          </div>

          <div class="form-group">
            <label>End Date *</label>
            <input v-model="form.end_date" type="date" required class="form-control" />
            <span v-if="errors.end_date" class="error-text">{{ errors.end_date }}</span>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Assigned User *</label>
            <select v-model="form.user_id" required class="form-control">
              <option value="">Select a user</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
            <span v-if="errors.user_id" class="error-text">{{ errors.user_id }}</span>
          </div>

          <div class="form-group">
            <label>Status *</label>
            <select v-model="form.status_id" required class="form-control">
              <option value="">Select a status</option>
              <option v-for="status in statuses" :key="status.id" :value="status.id">
                {{ status.name }}
              </option>
            </select>
            <span v-if="errors.status_id" class="error-text">{{ errors.status_id }}</span>
          </div>
        </div>

        <div v-if="availabilityError" class="error-message">
          {{ availabilityError }}
        </div>

        <div class="modal-footer">
          <button type="button" @click="handleClose" class="btn-secondary">Cancel</button>
          <button type="submit" :disabled="loading" class="btn-primary">
            {{ loading ? 'Saving...' : editingTask ? 'Update' : 'Create' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop, Watch } from 'vue-property-decorator';
import { mapActions } from 'vuex';

@Component({
  props: {
    task: {
      type: Object,
      default: null,
    },
    users: {
      type: Array,
      required: true,
    },
    statuses: {
      type: Array,
      required: true,
    },
  },
  methods: {
    ...mapActions('tasks', ['createTask', 'updateTask']),
  },
})
export default class TaskModal extends Vue {
  task!: any;
  users!: any[];
  statuses!: any[];

  form = {
    title: '',
    description: '',
    start_date: '',
    end_date: '',
    user_id: null as number | null,
    status_id: null as number | null,
  };

  errors: any = {};
  availabilityError = '';
  loading = false;

  get editingTask() {
    return this.task;
  }

  @Watch('task', { immediate: true })
  onTaskChange() {
    if (this.task) {
      this.form = {
        title: this.task.title,
        description: this.task.description || '',
        start_date: this.task.start_date,
        end_date: this.task.end_date,
        user_id: this.task.user_id,
        status_id: this.task.status_id,
      };
    } else {
      this.form = {
        title: '',
        description: '',
        start_date: '',
        end_date: '',
        user_id: null,
        status_id: null,
      };
    }
    this.errors = {};
    this.availabilityError = '';
  }

  handleClose() {
    this.$emit('close');
  }

  async handleSubmit() {
    this.errors = {};
    this.availabilityError = '';
    this.loading = true;

    try {
      if (this.editingTask) {
        const result = await this.updateTask({
          id: this.editingTask.id,
          data: this.form,
        });

        if (result.success) {
          this.$emit('save');
        } else {
          if (result.message && result.message.includes('overlapping')) {
            this.availabilityError = result.message;
          } else {
            this.errors = result.errors || {};
          }
        }
      } else {
        const result = await this.createTask(this.form);

        if (result.success) {
          this.$emit('save');
        } else {
          if (result.message && result.message.includes('overlapping')) {
            this.availabilityError = result.message;
          } else {
            this.errors = result.errors || {};
          }
        }
      }
    } catch (error) {
      this.availabilityError = 'An error occurred. Please try again.';
    } finally {
      this.loading = false;
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #333;
}

.btn-close {
  background: none;
  border: none;
  font-size: 2rem;
  cursor: pointer;
  color: #999;
  line-height: 1;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  color: #333;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.875rem;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
}

.error-text {
  display: block;
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.error-message {
  background: #fee2e2;
  color: #ef4444;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background: white;
  color: #333;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
}

.btn-secondary:hover {
  background: #f9fafb;
}

.btn-primary {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
}

.btn-primary:hover:not(:disabled) {
  background: #5568d3;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

