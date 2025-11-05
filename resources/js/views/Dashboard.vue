<template>
  <div class="dashboard">
    <header class="dashboard-header">
      <h1>Task Scheduling Platform</h1>
      <div class="header-actions">
        <span class="user-name">{{ user?.name }}</span>
        <button @click="handleLogout" class="btn-logout">Logout</button>
      </div>
    </header>
    <div class="dashboard-content">
      <TaskBoard />
    </div>
  </div>
</template>

<script lang="ts">
import { Vue, Component } from 'vue-property-decorator';
import { mapGetters } from 'vuex';
import TaskBoard from '@/components/TaskBoard.vue';

@Component({
  components: {
    TaskBoard,
  },
  computed: {
    ...mapGetters('auth', ['user']),
  },
})
export default class Dashboard extends Vue {
  async handleLogout() {
    await this.$store.dispatch('auth/logout');
    this.$router.push('/login');
  }
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background-color: #f5f5f5;
}

.dashboard-header {
  background: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dashboard-header h1 {
  font-size: 1.5rem;
  color: #333;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-name {
  color: #666;
  font-weight: 500;
}

.btn-logout {
  padding: 0.5rem 1rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: background 0.2s;
}

.btn-logout:hover {
  background: #dc2626;
}

.dashboard-content {
  padding: 2rem;
}
</style>

