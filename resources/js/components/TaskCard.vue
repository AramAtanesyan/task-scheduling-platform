<template>
  <div class="task-card">
    <div class="task-header">
      <h4 class="task-title">{{ task.title }}</h4>
      <div class="task-actions">
        <button @click="$emit('edit', task)" class="btn-icon">✏️</button>
        <button @click="$emit('delete', task.id)" class="btn-icon">🗑️</button>
      </div>
    </div>
    <p v-if="task.description" class="task-description">{{ task.description }}</p>
    <div class="task-meta">
      <div class="task-assignee">
        <strong>Assigned to:</strong> {{ task.user?.name || 'Unassigned' }}
      </div>
      <div class="task-dates">
        <div><strong>Start:</strong> {{ formatDate(task.start_date) }}</div>
        <div><strong>End:</strong> {{ formatDate(task.end_date) }}</div>
      </div>
      <div
        class="task-status"
        :style="{ backgroundColor: task.status?.color + '20', color: task.status?.color }"
      >
        {{ task.status?.name }}
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop } from 'vue-property-decorator';

@Component
export default class TaskCard extends Vue {
  @Prop({ required: true }) task!: any;

  formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  }
}
</script>

<style scoped>
.task-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  transition: box-shadow 0.2s;
}

.task-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 0.5rem;
}

.task-title {
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  margin: 0;
  flex: 1;
}

.task-actions {
  display: flex;
  gap: 0.25rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  padding: 0.25rem;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.btn-icon:hover {
  opacity: 1;
}

.task-description {
  color: #666;
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
  line-height: 1.4;
}

.task-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.task-assignee {
  color: #555;
}

.task-dates {
  display: flex;
  gap: 1rem;
  color: #666;
}

.task-status {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
  font-size: 0.75rem;
  width: fit-content;
}
</style>

