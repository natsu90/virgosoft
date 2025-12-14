<script setup>
    import { defineProps, ref, useId } from 'vue';
    import { router } from '@inertiajs/vue3'
    import { useToast } from "vue-toastification"

    const toast = useToast()

    const formData = ref({
        email:'',
        password:''
    })

    async function submit() {

        try {
            const response = await axios.post('/api/login', formData.value)
            const token = response.data.data.token
            // save token for future request
            localStorage.setItem('userToken', token)

            toast.success(response.data.message)
            router.visit('/')
        } catch (error) {
            toast.error(error.response.data.message)
        }
    }
</script>

<template>
  <!--
    This example requires updating your template:

    ```
    <html class="h-full bg-gray-900">
    <body class="h-full">
    ```
  -->
  <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <img class="mx-auto h-10 w-auto" src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company" />
      <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-white">Sign in to your account</h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <form @submit.prevent="submit" class="space-y-6" action="#" method="POST">
        <div>
          <label for="email" class="block text-sm/6 font-medium text-gray-100">Email address</label>
          <div class="mt-2">
            <input v-model="formData.email" type="email" name="email" id="email" autocomplete="email" required="" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between">
            <label for="password" class="block text-sm/6 font-medium text-gray-100">Password</label>
          </div>
          <div class="mt-2">
            <input v-model="formData.password" type="password" name="password" id="password" autocomplete="current-password" required="" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" />
          </div>
        </div>

        <div>
          <v-button @click="submit" class="flex w-full justify-center rounded-md bg-indigo-500 px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">Sign in</v-button>
        </div>
      </form>

      <p class="mt-10 text-center text-sm/6 text-gray-400">
        Not a member?
        {{ ' ' }}
        <a href="/register" class="font-semibold text-indigo-400 hover:text-indigo-300">Register an account</a>
      </p>
    </div>
  </div>
</template>
