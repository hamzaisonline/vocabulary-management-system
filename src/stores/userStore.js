import {
  createUser,
  deleteUser,
  getUserById,
  getUsers,
  updateUser
} from '@/service/userService';
import { defineStore } from 'pinia';

export const useUserStore = defineStore('userStore', {
  state: () => ({
    data: [],
    selectedUser: null,
    filters: {
      name: '',
      email: '',
      mobile: ''
    },
    displayedData: [],
    itemsPerPageOptions: [20, 100, 500],
    activeItemsPerPage: 20,
    currentPage: 1,
    isLoading: false,
    sortColumn: '',
    sortOrder: 'asc',
    isFiltersActive: false,
    selectedIds: []
  }),
  getters: {
    normalizedPhone() {
      return (mobile) => mobile.replace(/^0|\D/g, '');
    },
    filteredRows(state) {
      let filtered = state.data.filter((user) => {
        const nameMatch =
          !state.filters.name ||
          (user.name && user.name.toLowerCase().includes(state.filters.name.toLowerCase()))
        const emailMatch =
          !state.filters.email ||
          (user.email && user.email.toLowerCase().includes(state.filters.email.toLowerCase()))
        const mobileMatch =
          !state.filters.mobile || 
          (user.phone && this.normalizedPhone(user.phone).includes(this.normalizedPhone(state.filters.mobile)))           

        return nameMatch && emailMatch && mobileMatch
      })

      filtered = filtered.sort((a, b) => {
        const valueA = a[state.sortColumn]
        const valueB = b[state.sortColumn]

        if (state.sortOrder === 'asc') {
          return valueA < valueB ? -1 : valueA > valueB ? 1 : 0
        } else {
          return valueA > valueB ? -1 : valueA < valueB ? 1 : 0
        }
      })

      return filtered
    },
    paginatedUsers(state) {
      const start = 0
      const end = state.currentPage * state.activeItemsPerPage
      return this.filteredRows.slice(start, end)
    }
  },
  actions: {
    async fetchUsers() {
      this.isLoading = true
      try {
        const users = await getUsers()
        this.data = users
        this.loadUsers()
        return { data: users }
      } finally {
        this.isLoading = false
      }
    },
    async fetchUserById(id) {
      this.isLoading = true
      try {
        const user = await getUserById(id) 
        this.selectedUser = user
        return user
      } catch (error) {
        console.error('Error fetching user:', error)
      } finally {
        this.isLoading = false
      }
    },
    async storeUser(userData) {
      this.isLoading = true
      try {
        const newUser = await createUser(userData)
        this.data.push(newUser)
        return newUser
      } catch (err) {
        console.error('Error storing user:', err)
        addNotice(err.response.data.messages, 'info')
      } finally {
        this.isLoading = false
      }
    },
    async updateUser(id, updatedData) {
      this.isLoading = true
      try {
        const response = await updateUser(id, updatedData)
        const index = this.data.findIndex((user) => user.id === id)
        if (index !== -1) {
          this.data[index] = response.data
        }
        return response
      } catch (err) {
        console.error('Error updating user:', err)
      } finally {
        this.isLoading = false
      }
    },
    async deleteUser(id) {
      this.isLoading = true
      try {
        const response = await deleteUser(id)
        this.data = this.data.filter((user) => user.id !== id)
        if(response.success) {
          console.log(response)
          addNotice(response.message, 'success')
        }
        return response
      } catch (err) {
        console.error('Error deleting user:', err)
      } finally {
        this.isLoading = false
      }
    },
    setSelectedUser(selectedUser) {
      this.selectedUser = selectedUser
    },
    loadUsers() {
      this.displayedData = this.paginatedUsers
    },
    nextPage() {
      const maxPages = Math.ceil(this.filteredRows.length / this.activeItemsPerPage)
      if (this.currentPage < maxPages) {
        this.currentPage++
        this.loadUsers()
      }
    },
    previousPage() {
      if (this.currentPage > 1) {
        this.currentPage--
        this.loadUsers()
      }
    },
    changeItemsPerPage(newLimit) {
      this.activeItemsPerPage = newLimit
      this.currentPage = 1
      this.loadUsers()
    },
    loadMore() {
      this.currentPage += 1
      this.loadUsers()
    },
    updateFilter(key, value) {
      this.filters[key] = value
      this.currentPage = 1
      this.loadUsers()
    },
    resetFilters() {
      this.filters = {
        name: '',
        email: '',
        mobile: ''
      }
      this.currentPage = 1
      this.loadUsers()
    },
    sortData(column, order) {
      this.sortColumn = column
      this.sortOrder = order
      this.currentPage = 1
      this.loadUsers()
    },
    selectAll() {
      if (this.selectedIds.length === this.displayedData.length) {
        this.selectedIds = []
      } else {
        this.selectedIds = this.displayedData.map((item) => item.id)
      }
    },
    toggleSelect(id) {
      if (this.selectedIds.includes(id)) {
        this.selectedIds = this.selectedIds.filter((selectedId) => selectedId !== id)
      } else {
        this.selectedIds.push(id)
      }
    },
    allSelected() {
      return this.selectedIds.length === this.displayedData.length
    }
  }
})
