/**
 * App Header Component
 *
 * Reusable header component with user info and logout functionality
 */

window.Vue.component('app-header', {
  template: `
    <header class="dashboard-header">
      <h1>{{ title }}</h1>
      <div class="header-actions">
        <slot name="actions"></slot>
        <button v-if="showUser && user && user.role === 'admin'" 
                @click="$emit('add-user')" 
                class="btn-secondary">
          Add User
        </button>
        <span v-if="showUser && user" class="user-name">{{ user.name }}</span>
        <button @click="handleLogout" class="btn-logout">Logout</button>
      </div>
    </header>
  `,
  props: {
    title: {
      type: String,
      default: 'Task Scheduling Platform'
    },
    showUser: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      user: null
    };
  },
  async mounted() {
    if (this.showUser) {
      await this.fetchUser();
    }
  },
  methods: {
    async fetchUser() {
      try {
        const response = await axios.get('/api/user');
        this.user = response.data;
      } catch (error) {
        console.error('Error fetching user:', error);
      }
    },
    async handleLogout() {
      try {
        await axios.post('/logout');
        window.location.href = '/login';
      } catch (error) {
        console.error('Logout error:', error);
        window.location.href = '/login';
      }
    }
  }
});

