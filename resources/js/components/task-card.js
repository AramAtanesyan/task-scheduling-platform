/**
 * Task Card Component
 * 
 * Displays individual task information
 */

window.Vue.component('task-card', {
  template: `
    <div class="task-card">
      <div class="task-header">
        <h4 class="task-title">{{ task.title }}</h4>
        <div class="task-actions">
          <button @click="$emit('edit', task)" class="btn-icon" :title="canEdit ? 'Edit task' : 'View task'">
            {{ canEdit ? '✏️' : '👁️' }}
          </button>
          <button v-if="isAdmin" @click="$emit('delete', task.id)" class="btn-icon" title="Delete task">🗑️</button>
        </div>
      </div>
      <p v-if="task.description" class="task-description">{{ task.description }}</p>
      <div class="task-meta">
        <div class="task-assignee">
          <strong>Assigned to:</strong> {{ task.user ? task.user.name : 'Unassigned' }}
        </div>
        <div class="task-dates">
          <div><strong>Start:</strong> {{ formatDate(task.start_date) }}</div>
          <div><strong>End:</strong> {{ formatDate(task.end_date) }}</div>
        </div>
        <div
          class="task-status"
          :style="{ backgroundColor: task.status.color + '20', color: task.status.color }"
        >
          {{ task.status.name }}
        </div>
      </div>
    </div>
  `,
  props: {
    task: {
      type: Object,
      required: true
    },
    currentUser: {
      type: Object,
      default: null
    }
  },
  computed: {
    isAdmin() {
      return this.currentUser && this.currentUser.role === 'admin';
    },
    canEdit() {
      return this.isAdmin || (this.currentUser && this.task.user_id === this.currentUser.id);
    }
  },
  methods: {
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    }
  }
});

