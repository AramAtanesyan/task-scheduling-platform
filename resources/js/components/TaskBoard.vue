<template>
  <div class="task-board">
    <div class="board-header">
      <div class="filters">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search tasks..."
          class="search-input"
          @input="handleSearch"
        />
        <select v-model="selectedStatus" @change="handleFilter" class="filter-select">
          <option :value="null">All Statuses</option>
          <option v-for="status in statuses" :key="status.id" :value="status.id">
            {{ status.name }}
          </option>
        </select>
        <select v-model="selectedUser" @change="handleFilter" class="filter-select">
          <option :value="null">All Users</option>
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </select>
      </div>
      <button @click="openTaskModal" class="btn-create">Create Task</button>
    </div>

    <div class="view-toggle">
      <button
        :class="['toggle-btn', { active: viewMode === 'kanban' }]"
        @click="viewMode = 'kanban'"
      >
        Kanban
      </button>
      <button
        :class="['toggle-btn', { active: viewMode === 'list' }]"
        @click="viewMode = 'list'"
      >
        List
      </button>
    </div>

    <div v-if="viewMode === 'kanban'" class="kanban-board">
      <div
        v-for="status in statuses"
        :key="status.id"
        class="kanban-column"
        :style="{ borderTop: `4px solid ${status.color}` }"
      >
        <h3 class="column-header">{{ status.name }}</h3>
        <div class="column-content">
          <TaskCard
            v-for="task in getTasksByStatus(status.id)"
            :key="task.id"
            :task="task"
            @edit="handleEdit"
            @delete="handleDelete"
          />
          <div v-if="getTasksByStatus(status.id).length === 0" class="empty-column">
            No tasks
          </div>
        </div>
      </div>
    </div>

    <div v-else class="list-view">
      <TaskCard
        v-for="task in filteredTasks"
        :key="task.id"
        :task="task"
        @edit="handleEdit"
        @delete="handleDelete"
      />
      <div v-if="filteredTasks.length === 0" class="empty-list">
        No tasks found
      </div>
    </div>

    <TaskModal
      v-if="showModal"
      :task="editingTask"
      :users="users"
      :statuses="statuses"
      @close="closeTaskModal"
      @save="handleSave"
    />
  </div>
</template>

<script lang="ts">
import { Vue, Component, Watch } from 'vue-property-decorator';
import { mapGetters, mapActions } from 'vuex';
import TaskCard from './TaskCard.vue';
import TaskModal from './TaskModal.vue';

@Component({
  components: {
    TaskCard,
    TaskModal,
  },
  computed: {
    ...mapGetters('tasks', ['tasks', 'filters']),
    ...mapGetters('users', ['users']),
    ...mapGetters('statuses', ['statuses']),
  },
  methods: {
    ...mapActions('tasks', ['fetchTasks', 'setFilters', 'deleteTask']),
    ...mapActions('users', ['fetchUsers']),
    ...mapActions('statuses', ['fetchStatuses']),
  },
})
export default class TaskBoard extends Vue {
  viewMode: 'kanban' | 'list' = 'kanban';
  showModal = false;
  editingTask: any = null;
  searchQuery = '';
  selectedStatus: number | null = null;
  selectedUser: number | null = null;

  async mounted() {
    await Promise.all([
      this.fetchTasks(),
      this.fetchUsers(),
      this.fetchStatuses(),
    ]);
  }

  get filteredTasks() {
    return this.tasks;
  }

  getTasksByStatus(statusId: number) {
    return this.tasks.filter((task: any) => task.status_id === statusId);
  }

  handleSearch() {
    this.setFilters({ search: this.searchQuery });
    this.fetchTasks();
  }

  handleFilter() {
    this.setFilters({
      status_id: this.selectedStatus,
      user_id: this.selectedUser,
    });
    this.fetchTasks();
  }

  openTaskModal(task?: any) {
    this.editingTask = task || null;
    this.showModal = true;
  }

  closeTaskModal() {
    this.showModal = false;
    this.editingTask = null;
  }

  handleEdit(task: any) {
    this.openTaskModal(task);
  }

  async handleDelete(taskId: number) {
    if (confirm('Are you sure you want to delete this task?')) {
      await this.deleteTask(taskId);
      await this.fetchTasks();
    }
  }

  async handleSave() {
    await this.fetchTasks();
    this.closeTaskModal();
  }
}
</script>

<style scoped>
.task-board {
  max-width: 1400px;
  margin: 0 auto;
}

.board-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.filters {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.875rem;
}

.search-input {
  min-width: 200px;
}

.filter-select {
  min-width: 150px;
}

.btn-create {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-create:hover {
  background: #5568d3;
}

.view-toggle {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.toggle-btn {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.toggle-btn.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.kanban-board {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.kanban-column {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.column-header {
  margin-bottom: 1rem;
  font-size: 1rem;
  font-weight: 600;
  color: #333;
}

.column-content {
  min-height: 200px;
}

.empty-column {
  text-align: center;
  color: #999;
  padding: 2rem;
}

.list-view {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.empty-list {
  text-align: center;
  color: #999;
  padding: 3rem;
  background: white;
  border-radius: 8px;
}
</style>

