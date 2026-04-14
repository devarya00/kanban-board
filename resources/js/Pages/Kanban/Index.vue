<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3'; 
import { ref, watch } from 'vue'; 
import draggable from 'vuedraggable';

const props = defineProps({ boards: Array });
const localBoards = ref([]);

const buildMatrix = (boardsData) => {
    return JSON.parse(JSON.stringify(boardsData)).map(board => {
        board.headers = board.columns.map(c => ({ id: c.id, title: c.title, wip_limit: c.wip_limit }));

        board.swimlanes = [
            { id: 'Urgente', title: 'Expedite (Urgente)', color: 'text-red-400', border: 'border-red-500/30', badge: 'bg-red-500' },
            { id: 'Normal', title: 'Fluxo Normal', color: 'text-slate-200', border: 'border-[#303340]', badge: 'bg-indigo-500' },
            { id: 'Baixa', title: 'Baixa Prioridade', color: 'text-slate-500', border: 'border-[#303340]', badge: 'bg-slate-500' }
        ];

        board.swimlanes.forEach(swimlane => {
            swimlane.cardsByColumn = {};
            board.columns.forEach(column => {
                if (swimlane.id === 'Normal') {
                    swimlane.cardsByColumn[column.id] = column.cards.filter(c => !c.priority || c.priority === 'Normal');
                } else {
                    swimlane.cardsByColumn[column.id] = column.cards.filter(c => c.priority === swimlane.id);
                }
            });
        });
        return board;
    });
};

watch(() => props.boards, (newVal) => { localBoards.value = buildMatrix(newVal); }, { deep: true, immediate: true });

const boardForm = useForm({ title: '' });
const createBoard = () => { boardForm.post(route('kanban.store'), { onSuccess: () => boardForm.reset('title') }); };
const deleteBoard = (boardId) => { if (confirm('Excluir este projeto?')) router.delete(route('boards.destroy', boardId)); };

const newColumnTitles = ref({});
const newColumnWip = ref({});
const createColumn = (boardId) => {
    if (!newColumnTitles.value[boardId]) return;
    router.post(route('columns.store', boardId), { title: newColumnTitles.value[boardId], wip_limit: newColumnWip.value[boardId] }, {
        preserveScroll: true, onSuccess: () => { newColumnTitles.value[boardId] = ''; newColumnWip.value[boardId] = ''; }
    });
};

const checkMove = (evt) => {
    const toColumnId = parseInt(evt.to.dataset.columnId);
    if (toColumnId === parseInt(evt.from.dataset.columnId)) return true;

    let targetBoard = localBoards.value.find(b => b.headers.some(h => h.id === toColumnId));
    if(!targetBoard) return true;

    let targetHeader = targetBoard.headers.find(h => h.id === toColumnId);
    if (targetHeader && targetHeader.wip_limit !== null) {
        let currentCount = 0;
        targetBoard.swimlanes.forEach(sl => { currentCount += (sl.cardsByColumn[toColumnId] || []).length; });
        if (currentCount >= targetHeader.wip_limit) return false; 
    }
    return true;
};

const newCardTitles = ref({});
const createCard = (columnId, priority) => {
    const key = `${columnId}-${priority}`;
    if (!newCardTitles.value[key]) return;
    router.post(route('cards.store', columnId), { title: newCardTitles.value[key], priority: priority }, {
        preserveScroll: true, onSuccess: () => { newCardTitles.value[key] = ''; }
    });
};

const editingCardId = ref(null); 
const editCardForm = useForm({ title: '', priority: 'Normal' });

const startEditing = (card) => {
    editingCardId.value = card.id;
    editCardForm.title = card.title;
    editCardForm.priority = card.priority || 'Normal';
};

const updateCard = (cardId) => {
    editCardForm.put(route('cards.update', cardId), { preserveScroll: true, onSuccess: () => { editingCardId.value = null; } });
};

const deleteCard = (cardId) => { if (confirm('Apagar tarefa?')) router.delete(route('cards.destroy', cardId)); };

const handleDragChange = (evt, columnId, priorityId) => {
    if (evt.added) {
        router.put(route('cards.update', evt.added.element.id), {
            column_id: columnId,
            priority: priorityId
        }, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="My Tasks" />

    <div class="h-screen w-full bg-[#1a1b21] font-sans text-slate-300 flex overflow-hidden">
        
        <aside class="w-64 bg-[#1e1f26] border-r border-[#303340] flex-col justify-between hidden md:flex flex-shrink-0">
            <div>
                <div class="h-20 flex items-center px-6 border-b border-[#303340]/50">
                    <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center font-bold text-white mr-3 shadow-lg shadow-indigo-500/30">K</div>
                    <span class="font-bold text-lg text-slate-100 tracking-wide">Workspace</span>
                </div>
                
                <div class="p-4 space-y-1">
                    <button class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2 px-4 rounded-lg mb-6 transition shadow-md shadow-indigo-900/20 text-sm">Quick Action</button>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 px-2 mt-4">Menu</div>
                    <Link :href="route('overview')" :class="route().current('overview') ? 'bg-[#262833] text-slate-100 shadow-sm border border-slate-700/50' : 'text-slate-400 hover:text-slate-200 hover:bg-[#262833]'" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition group">Overview</Link>
                    <Link :href="route('kanban.index')" :class="route().current('kanban.index') ? 'bg-[#262833] text-slate-100 shadow-sm border border-slate-700/50' : 'text-slate-400 hover:text-slate-200 hover:bg-[#262833]'" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition group">My Tasks</Link>
                </div>
            </div>
            <div class="p-4 border-t border-[#303340]/50">
                <Link :href="route('dashboard')" class="text-sm text-slate-400 hover:text-slate-200">Back to Dashboard</Link>
            </div>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#1a1b21]">
            <header class="h-20 flex items-center justify-between px-8 border-b border-[#303340] bg-[#1e1f26]">
                <h2 class="text-2xl font-bold text-slate-100 tracking-tight">My Tasks</h2>
                <div class="text-sm text-slate-400">Hello, {{ $page.props.auth.user.name }}</div>
            </header>

            <div class="flex-1 overflow-x-auto overflow-y-auto p-8 hide-scrollbar">
                <div class="max-w-[1600px] pb-20">
                    
                    <div class="bg-[#262833] p-4 rounded-xl shadow-sm mb-10 border border-[#303340] max-w-2xl">
                        <form @submit.prevent="createBoard" class="flex gap-4">
                            <input v-model="boardForm.title" type="text" placeholder="New project name..." class="flex-1 bg-[#1e1f26] border-transparent text-slate-200 focus:ring-indigo-500 rounded-lg" required>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2 px-6 rounded-lg transition">Create Project</button>
                        </form>
                    </div>

                    <div v-if="localBoards.length > 0" v-for="board in localBoards" :key="board.id" class="mb-16">
                        <div class="flex items-center gap-6 mb-6">
                            <h3 class="text-2xl font-bold text-slate-200">{{ board.title }}</h3>
                            <div class="h-px flex-1 bg-[#303340]"></div>
                            <button @click="deleteBoard(board.id)" class="text-slate-500 hover:text-red-400 text-sm font-medium">Delete Project</button>
                        </div>
                        
                        <div class="inline-flex flex-col min-w-max bg-[#1e1f26]/30 p-6 rounded-2xl border border-[#303340]/50">
                            
                            <div class="flex gap-6 mb-4 pl-4">
                                <div v-for="header in board.headers" :key="'h-'+header.id" class="w-[300px] flex justify-between items-center pb-2 border-b-2 border-slate-700/50">
                                    <h4 class="font-bold text-slate-300 tracking-wide uppercase text-sm">{{ header.title }}</h4>
                                    <span v-if="header.wip_limit" class="text-xs font-bold px-2 py-0.5 rounded bg-[#262833] border border-slate-700 text-slate-400">WIP: {{ header.wip_limit }}</span>
                                </div>
                                
                                <div class="w-[300px] pb-2">
                                    <form @submit.prevent="createColumn(board.id)" class="flex gap-2">
                                        <input v-model="newColumnTitles[board.id]" type="text" placeholder="+ Add Column" class="w-full bg-transparent border-transparent text-slate-500 text-sm focus:ring-0 px-0">
                                        <input v-if="newColumnTitles[board.id]" v-model="newColumnWip[board.id]" type="number" placeholder="WIP" class="w-16 bg-[#262833] border-transparent text-slate-300 text-xs rounded px-2">
                                        <button v-if="newColumnTitles[board.id]" type="submit" class="hidden"></button>
                                    </form>
                                </div>
                            </div>

                            <div v-for="swimlane in board.swimlanes" :key="swimlane.id" class="mb-6 last:mb-0 relative">
                                
                                <div class="flex items-center gap-3 mb-3 sticky left-0">
                                    <h3 class="text-xs font-bold uppercase tracking-wider" :class="swimlane.color">{{ swimlane.title }}</h3>
                                    <div class="h-px w-24 bg-[#303340]/50"></div>
                                </div>

                                <div class="flex gap-6 pl-4">
                                    <div v-for="header in board.headers" :key="'cell-'+header.id+'-'+swimlane.id" class="w-[300px] bg-[#262833]/50 rounded-xl p-3 border min-h-[100px] flex flex-col transition-colors duration-300" :class="swimlane.border">
                                        
                                        <draggable 
                                            v-model="swimlane.cardsByColumn[header.id]" 
                                            group="cards" item-key="id" 
                                            :move="checkMove" :data-column-id="header.id" 
                                            @change="(evt) => handleDragChange(evt, header.id, swimlane.id)"
                                            class="flex-1 space-y-3 min-h-[40px] pb-1 custom-scrollbar"
                                        >
                                            <template #item="{ element: card }">
                                                <div class="bg-[#303340] p-4 rounded-xl shadow-sm cursor-grab active:cursor-grabbing border border-[#3e4252] hover:border-slate-400 group">
                                                    
                                                    <div v-if="editingCardId !== card.id">
                                                        <div class="w-8 h-1.5 rounded-full mb-3" :class="swimlane.badge"></div>
                                                        <div class="flex justify-between items-start">
                                                            <p class="text-sm font-medium text-slate-200">{{ card.title }}</p>
                                                            <div class="flex gap-2 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <button @click="startEditing(card)" class="text-slate-400 hover:text-indigo-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                                                                <button @click="deleteCard(card.id)" class="text-slate-400 hover:text-red-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div v-else>
                                                        <form @submit.prevent="updateCard(card.id)" class="space-y-2">
                                                            <input v-model="editCardForm.title" type="text" class="w-full text-sm bg-[#1e1f26] border-transparent rounded text-slate-200 focus:ring-indigo-500">
                                                            <select v-model="editCardForm.priority" class="w-full text-sm bg-[#1e1f26] border-transparent rounded text-slate-300 focus:ring-indigo-500">
                                                                <option value="Urgente">Urgente</option>
                                                                <option value="Normal">Normal</option>
                                                                <option value="Baixa">Baixa</option>
                                                            </select>
                                                            <div class="flex gap-2"><button type="submit" class="bg-indigo-600 text-white font-medium text-xs px-3 py-1.5 rounded">Save</button><button type="button" @click="editingCardId = null" class="text-slate-400 text-xs px-3 py-1.5">Cancel</button></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </template>
                                        </draggable>

                                        <form @submit.prevent="createCard(header.id, swimlane.id)" class="mt-1">
                                            <input v-model="newCardTitles[`${header.id}-${swimlane.id}`]" type="text" placeholder="+ Add task..." class="w-full bg-transparent border-transparent text-slate-500 text-sm focus:ring-0 px-2 py-1 hover:bg-[#303340]/50 rounded transition-colors">
                                            <button v-if="newCardTitles[`${header.id}-${swimlane.id}`]" type="submit" class="hidden"></button>
                                        </form>
                                    </div>
                                    <div class="w-[300px]"></div> </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #3e4252; border-radius: 10px; }
</style>