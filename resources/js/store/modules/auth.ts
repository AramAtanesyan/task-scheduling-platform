import { authApi } from '@/services/api';

interface AuthState {
  user: any | null;
  token: string | null;
  isAuthenticated: boolean;
}

const state: AuthState = {
  user: JSON.parse(localStorage.getItem('user') || 'null'),
  token: localStorage.getItem('auth_token'),
  isAuthenticated: !!localStorage.getItem('auth_token'),
};

const mutations = {
  SET_USER(state: AuthState, user: any) {
    state.user = user;
    state.isAuthenticated = true;
    localStorage.setItem('user', JSON.stringify(user));
  },
  SET_TOKEN(state: AuthState, token: string) {
    state.token = token;
    localStorage.setItem('auth_token', token);
  },
  CLEAR_AUTH(state: AuthState) {
    state.user = null;
    state.token = null;
    state.isAuthenticated = false;
    localStorage.removeItem('user');
    localStorage.removeItem('auth_token');
  },
};

const actions = {
  async login({ commit }: any, { email, password }: { email: string; password: string }) {
    try {
      const response = await authApi.login(email, password);
      commit('SET_TOKEN', response.data.token);
      commit('SET_USER', response.data.user);
      return { success: true };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Login failed',
      };
    }
  },
  async logout({ commit }: any) {
    try {
      await authApi.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      commit('CLEAR_AUTH');
    }
  },
  async fetchUser({ commit }: any) {
    try {
      const response = await authApi.getUser();
      commit('SET_USER', response.data);
      return { success: true };
    } catch (error) {
      commit('CLEAR_AUTH');
      return { success: false };
    }
  },
};

const getters = {
  user: (state: AuthState) => state.user,
  isAuthenticated: (state: AuthState) => state.isAuthenticated,
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters,
};

