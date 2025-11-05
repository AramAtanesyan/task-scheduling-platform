import { tasksApi } from '@/services/api';

interface Task {
  id: number;
  title: string;
  description: string;
  start_date: string;
  end_date: string;
  status_id: number;
  user_id: number;
  user?: any;
  status?: any;
}

interface TasksState {
  tasks: Task[];
  filters: {
    search: string;
    status_id: number | null;
    user_id: number | null;
  };
  loading: boolean;
}

const state: TasksState = {
  tasks: [],
  filters: {
    search: '',
    status_id: null,
    user_id: null,
  },
  loading: false,
};

const mutations = {
  SET_TASKS(state: TasksState, tasks: Task[]) {
    state.tasks = tasks;
  },
  ADD_TASK(state: TasksState, task: Task) {
    state.tasks.push(task);
  },
  UPDATE_TASK(state: TasksState, task: Task) {
    const index = state.tasks.findIndex((t) => t.id === task.id);
    if (index !== -1) {
      state.tasks.splice(index, 1, task);
    }
  },
  REMOVE_TASK(state: TasksState, taskId: number) {
    state.tasks = state.tasks.filter((t) => t.id !== taskId);
  },
  SET_FILTERS(state: TasksState, filters: any) {
    state.filters = { ...state.filters, ...filters };
  },
  SET_LOADING(state: TasksState, loading: boolean) {
    state.loading = loading;
  },
};

const actions = {
  async fetchTasks({ commit, state }: any) {
    commit('SET_LOADING', true);
    try {
      const params: any = {};
      if (state.filters.search) params.search = state.filters.search;
      if (state.filters.status_id) params.status_id = state.filters.status_id;
      if (state.filters.user_id) params.user_id = state.filters.user_id;

      const response = await tasksApi.getAll(params);
      commit('SET_TASKS', response.data);
      return { success: true };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch tasks',
      };
    } finally {
      commit('SET_LOADING', false);
    }
  },
  async createTask({ commit }: any, taskData: any) {
    try {
      const response = await tasksApi.create(taskData);
      commit('ADD_TASK', response.data);
      return { success: true, data: response.data };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create task',
        errors: error.response?.data?.errors,
      };
    }
  },
  async updateTask({ commit }: any, { id, data }: { id: number; data: any }) {
    try {
      const response = await tasksApi.update(id, data);
      commit('UPDATE_TASK', response.data);
      return { success: true, data: response.data };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update task',
        errors: error.response?.data?.errors,
      };
    }
  },
  async deleteTask({ commit }: any, id: number) {
    try {
      await tasksApi.delete(id);
      commit('REMOVE_TASK', id);
      return { success: true };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete task',
      };
    }
  },
  async reassignTask({ commit }: any, { id, userId }: { id: number; userId: number }) {
    try {
      const response = await tasksApi.reassign(id, userId);
      commit('UPDATE_TASK', response.data);
      return { success: true, data: response.data };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to reassign task',
      };
    }
  },
  setFilters({ commit }: any, filters: any) {
    commit('SET_FILTERS', filters);
  },
};

const getters = {
  tasks: (state: TasksState) => state.tasks,
  filteredTasks: (state: TasksState) => state.tasks,
  filters: (state: TasksState) => state.filters,
  loading: (state: TasksState) => state.loading,
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters,
};

