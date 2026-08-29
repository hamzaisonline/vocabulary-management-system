import api from '@/api/api';

export const register = async (payload) => {
  const response = await api.post('/auth/register', payload);
  return response.data;
};

export const login = async (credentials) => {
  const payload = {
    email: credentials.email ?? credentials.username,
    password: credentials.password,
  };

  const response = await api.post('/auth/login', payload);
  return response.data;
};

export const fetchCurrentUser = async () => {
  const response = await api.get('/auth/me');
  return response.data;
};

export const logout = async () => {
  const response = await api.post('/auth/logout');
  return response.data;
};
