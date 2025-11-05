import { usersApi } from '@/services/api';

interface User {
  id: number;
  name: string;
  email: string;
}

interface UsersState {
  users: User[];
}

const state: UsersState = {
  users: [],
};

const mutations = {
  SET_USERS(state: UsersState, users: User[]) {
    state.users = users;
  },
};

const actions = {
  async fetchUsers({ commit }: any) {
    try {
      const response = await usersApi.getAll();
      commit('SET_USERS', response.data);
      return { success: true };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch users',
      };
    }
  },
};

const getters = {
  users: (state: UsersState) => state.users,
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters,
};

