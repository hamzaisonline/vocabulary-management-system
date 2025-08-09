// src/services/userService.js
import { del, get, post, put } from '@/api/api';

export const getUsers = async (params = {}) => {
    const response = await get('/user/all', params)
    return response.data
};

export const getUserById = async (id) => {
    const response = await get(`/user/detail/${id}`)
    return response.data
};

export const createUser = async (data) => { 
    const response = await post('/register', data)
    return response
};
export const updateUser = async (id, data) => { 
   const response = await put(`/user/update/${id}`, data) 
   return response
};
export const deleteUser = async (id) => { 
    const response = del(`/user/delete/${id}`); 
    return response
}

export const getCashiers = async () => { 
    const response = await get('/cashier/all');
    return response 
}