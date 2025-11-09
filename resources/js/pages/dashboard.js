/**
 * Dashboard Page
 */

// Load dependencies
require('../bootstrap');
window.Vue = require('vue');

// Load components
require('../components/loader');
require('../components/app-header');
require('../components/task-card');
require('../components/task-modal');
require('../components/user-modal');
require('../components/confirm-modal');
require('../components/task-board');

// Define dashboard layout component
window.Vue.component('dashboard-layout', {
  template: `
    <div class="dashboard">
      <app-header 
        title="Task Scheduling Platform" 
        :show-user="true"
        @add-user="showUserModal = true"
      >
        <template #actions>
          <a v-if="currentUser && currentUser.role === 'admin'" 
             href="/statuses" 
             class="btn-secondary">
            Manage Statuses
          </a>
        </template>
      </app-header>
      <div class="dashboard-content">
        <task-board ref="taskBoard" />
      </div>
      <user-modal
        v-if="showUserModal"
        @close="showUserModal = false"
        @save="handleUserSaved"
      />
    </div>
  `,
  data() {
    return {
      showUserModal: false,
      currentUser: null
    };
  },
  async mounted() {
    await this.fetchCurrentUser();
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
    handleUserSaved() {
      this.showUserModal = false;
      // Refresh only the users list (new user won't have tasks yet)
      if (this.$refs.taskBoard) {
        this.$refs.taskBoard.fetchUsers();
      }
    }
  }
});

// Initialize Vue app
new window.Vue({
  el: '#app'
});

