import Vue from 'vue';
import Vuex from 'vuex';
import auth from './modules/auth';
import tasks from './modules/tasks';
import users from './modules/users';
import statuses from './modules/statuses';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    auth,
    tasks,
    users,
    statuses,
  },
});

