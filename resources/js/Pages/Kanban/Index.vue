<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3'; 
import { ref } from 'vue';

defineProps({
    boards: Array,
});

const boardForm = useForm({
    title: '',
});

const createBoard = () => {
    boardForm.post(route('kanban.store'), {
        onSuccess: () => boardForm.reset('title'), 
    });
};

const newColumnTitles = ref({});

const createColumn = (boardId) => {
    const title = newColumnTitles.value[boardId];
    if (!title) return; 
  
    router.post(route('columns.store', boardId), { title: title }, {
        preserveScroll: true, 
        onSuccess: () => {
            newColumnTitles.value[boardId] = ''; 
        }
    });
};
</script>

<template>
    <Head title="Kanban board" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kanban board</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white p-4 rounded-lg shadow mb-8">
                    <form @submit.prevent="createBoard" class="flex gap-4">
                        <input 
                            v-model="boardForm.title" 
                            type="text" 
                            placeholder="Nome do novo projeto/quadro..." 
                            class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            required
                        >
                        <button 
                            type="submit" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                            :disabled="boardForm.processing"
                        >
                            Criar Quadro
                        </button>
                    </form>
                </div>

                <div v-if="boards.length > 0" v-for="board in boards" :key="board.id" class="mb-8">
                    <h3 class="text-2xl font-bold mb-4 text-gray-700">{{ board.title }}</h3>
                    
                    <div class="flex space-x-4 overflow-x-auto pb-4 items-start">
                        
                        <div v-for="column in board.columns" :key="column.id" class="bg-gray-200 rounded-lg p-4 w-72 flex-shrink-0">
                            <h4 class="font-bold text-gray-700 mb-3">{{ column.title }}</h4>
                            
                            <button class="mt-4 text-sm text-gray-500 hover:text-gray-700 font-medium">+ Adicionar Cartão</button>
                        </div>

                        <div class="bg-gray-100/50 border-2 border-dashed border-gray-300 rounded-lg p-4 w-72 flex-shrink-0">
                            <form @submit.prevent="createColumn(board.id)" class="w-full">
                                <input 
                                    v-model="newColumnTitles[board.id]" 
                                    type="text" 
                                    placeholder="+ Nova Lista (ex: A Fazer)" 
                                    class="w-full border-transparent focus:border-indigo-500 focus:ring-indigo-500 rounded bg-white shadow-sm text-sm"
                                    required
                                >
                                <div v-if="newColumnTitles[board.id]" class="mt-2 flex gap-2">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1.5 px-3 rounded">
                                        Adicionar
                                    </button>
                                    <button type="button" @click="newColumnTitles[board.id] = ''" class="text-gray-500 hover:text-gray-700 text-xs font-bold py-1.5 px-3">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
                
                <div v-else class="text-center bg-white p-6 rounded-lg shadow">
                    <p class="text-gray-500">Você ainda não tem nenhum quadro. Que tal criar o primeiro logo acima?</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>