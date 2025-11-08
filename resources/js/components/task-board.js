/**
 * Task Board Component
 * 
 * Main task management board with Kanban and List views
 */

window.Vue.component('task-board', {
  template: `
    <div class="task-board">
      <loader v-if="loading"></loader>
      <div v-else>
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
            <select v-model="selectedDueDate" @change="handleFilter" class="filter-select">
              <option :value="null">All Due Dates</option>
              <option value="overdue">Overdue</option>
              <option value="today">Due Today</option>
              <option value="this_week">This Week</option>
              <option value="this_month">This Month</option>
            </select>
          </div>
          <button v-if="currentUser && currentUser.role === 'admin'" 
                  @click="openTaskModal" 
                  class="btn-create">
            Create Task
          </button>
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
            :style="{ borderTop: '4px solid ' + status.color }"
          >
            <h3 class="column-header">{{ status.name }}</h3>
            <div class="column-content">
              <task-card
                v-for="task in getTasksByStatus(status.id)"
                :key="task.id"
                :task="task"
                :current-user="currentUser"
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
          <task-card
            v-for="task in filteredTasks"
            :key="task.id"
            :task="task"
            :current-user="currentUser"
            @edit="handleEdit"
            @delete="handleDelete"
          />
          <div v-if="filteredTasks.length === 0" class="empty-list">
            No tasks found
          </div>
        </div>

        <task-modal
          v-if="showModal"
          :task="editingTask"
          :users="users"
          :statuses="statuses"
          :current-user="currentUser"
          @close="closeTaskModal"
          @save="handleSave"
        />

        <confirm-modal ref="confirmModal" />
      </div>
    </div>
  `,
  data() {
    return {
      viewMode: 'kanban',
      showModal: false,
      editingTask: null,
      searchQuery: '',
      selectedStatus: null,
      selectedUser: null,
      selectedDueDate: null,
      tasks: [],
      users: [],
      statuses: [],
      loading: true,
      currentUser: null
    };
  },
  computed: {
    filteredTasks() {
      return this.tasks;
    }
  },
  async mounted() {
    await this.fetchCurrentUser();
    await this.fetchData();
  },
  methods: {
    async fetchCurrentUser() {
      try {
        const response = await axios.get('/api/user');
        this.currentUser = response.data;
      } catch (error) {
        console.error('Error fetching current user:', error);
      }
    },
    async fetchData() {
      this.loading = true;
      try {
        const [tasksRes, usersRes, statusesRes] = await Promise.all([
          axios.get('/api/tasks', {
            params: {
              search: this.searchQuery,
              status_id: this.selectedStatus,
              user_id: this.selectedUser,
              due_date_filter: this.selectedDueDate
            }
          }),
          axios.get('/api/users'),
          axios.get('/api/statuses')
        ]);

        this.tasks = tasksRes.data.data || tasksRes.data;
        this.users = usersRes.data.data || usersRes.data;
        this.statuses = statusesRes.data.data || statusesRes.data;
      } catch (error) {
        console.error('Error fetching data:', error);
      } finally {
        this.loading = false;
      }
    },
    async fetchUsers() {
      try {
        const response = await axios.get('/api/users');
        this.users = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    },
    async fetchTasks() {
      try {
        const response = await axios.get('/api/tasks', {
          params: {
            search: this.searchQuery,
            status_id: this.selectedStatus,
            user_id: this.selectedUser,
            due_date_filter: this.selectedDueDate
          }
        });
        this.tasks = response.data.data || response.data;
      } catch (error) {
        console.error('Error fetching tasks:', error);
      }
    },
    getTasksByStatus(statusId) {
      return this.tasks.filter(task => task.status_id === statusId);
    },
    handleSearch: _.debounce(function() {
      this.fetchTasks();
    }, 500),
    handleFilter() {
      this.fetchTasks();
    },
    openTaskModal(task = null) {
      // If task is an event object (from button click), set to null
      this.editingTask = (task && task.id) ? task : null;
      this.showModal = true;
    },
    closeTaskModal() {
      this.showModal = false;
      this.editingTask = null;
    },
    handleEdit(task) {
      this.openTaskModal(task);
    },
    async handleDelete(taskId) {
      // Show confirmation modal with async callback
      this.$refs.confirmModal.open({
        title: 'Delete Task',
        message: 'Are you sure you want to delete this task? This action cannot be undone.',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        dangerMode: true,
        onConfirm: async () => {
          // This will be called when user confirms
          await axios.delete(`/api/tasks/${taskId}`);
          await this.fetchTasks();
        }
      }).catch(error => {
        // Error is already displayed in the modal
        console.error('Error deleting task:', error);
      });
    },
    async handleSave() {
      await this.fetchTasks();
      this.closeTaskModal();
    }
  }
});

