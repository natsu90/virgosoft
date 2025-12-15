<script setup>
    import { defineProps, ref, useId } from 'vue';
    import { router } from '@inertiajs/vue3'
    import { useToast } from "vue-toastification"

    const toast = useToast()

    const formData = ref({
        email:'',
        password:'',
        password_confirmation:''
    })

    async function submit() {

        try {
            const response = await axios.post('/api/register', formData.value)

            toast.success(response.data.message)
            router.visit('/login')
        } catch (error) {
            toast.error(error.response.data.message)
        }
    }
</script>

<template>

<div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
  <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm rounded-xl border border-gray-200 py-8 px-6 max-w-90 w-full">
    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
        <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
    </div>

    <h3 class="mb-6 text-center text-xl font-bold text-gray-800">Create an Account</h3>

    <form @submit.prevent="submit">
        <div class="mb-4">
            <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
            <input v-model="formData.email" name="email" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" required="" />
        </div>

        <div class="mb-6">
            <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
            <input v-model="formData.password" name="password" type="password" class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" required="" />
        </div>

        <div class="mb-6">
            <label class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
            <input v-model="formData.password_confirmation" type="password" class="w-full rounded-lg border border-gray-300 px-3 py-2 transition outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" required="" />
        </div>

        <button @click="submit" class="w-full rounded-lg bg-blue-500 px-4 py-2 font-medium text-white transition duration-300 hover:bg-blue-600">Register</button>

        <div class="mt-4 text-center">
            <a href="/login" class="text-sm text-blue-500 hover:text-blue-600">Login here</a>
        </div>
    </form>
  </div>
</div>
</template>
