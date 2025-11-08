import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';

const apiClient: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Auth API
export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post('/login', { email, password }),
  logout: () => apiClient.post('/logout'),
  getUser: () => apiClient.get('/user'),
};

// Users API
export const usersApi = {
  getAll: () => apiClient.get('/users'),
  create: (data: { name: string; email: string; password: string; role: string }) =>
    apiClient.post('/users', data),
};

// Task Statuses API
export const statusesApi = {
  getAll: () => apiClient.get('/task-statuses'),
};

// Tasks API
export const tasksApi = {
  getAll: (params?: { search?: string; status_id?: number; user_id?: number }) =>
    apiClient.get('/tasks', { params }),
  getById: (id: number) => apiClient.get(`/tasks/${id}`),
  create: (data: any) => apiClient.post('/tasks', data),
  update: (id: number, data: any) => apiClient.put(`/tasks/${id}`, data),
  delete: (id: number) => apiClient.delete(`/tasks/${id}`),
};

export default apiClient;

