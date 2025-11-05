import { statusesApi } from '@/services/api';

interface Status {
  id: number;
  name: string;
  color: string;
}

interface StatusesState {
  statuses: Status[];
}

const state: StatusesState = {
  statuses: [],
};

const mutations = {
  SET_STATUSES(state: StatusesState, statuses: Status[]) {
    state.statuses = statuses;
  },
};

const actions = {
  async fetchStatuses({ commit }: any) {
    try {
      const response = await statusesApi.getAll();
      commit('SET_STATUSES', response.data);
      return { success: true };
    } catch (error: any) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch statuses',
      };
    }
  },
};

const getters = {
  statuses: (state: StatusesState) => state.statuses,
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
  getters,
};

