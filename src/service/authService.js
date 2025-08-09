import api from "@/api/api";

const BASE_URL = '/login';

export const login = async (credentials) => {
  const response = await api.post(BASE_URL, credentials);
  return response.data;
};

export const fetchUserProfile = async () => {
  const response = await api.get('user/profile');
  return response.data;
};

export const logout = async () => {
  const response = await api.post('logout');
  return response.data;
};

export const refreshToken = async (credentials) => {
  const response = await api.post(BASE_URL, credentials);
  return response.data;
};
