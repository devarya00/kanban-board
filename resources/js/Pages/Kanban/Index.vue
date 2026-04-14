<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3'; 
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'; 
import draggable from 'vuedraggable';

const props = defineProps({ boards: Array });
const localBoards = ref([]);

const buildMatrix = (boardsData) => {
    return JSON.parse(JSON.stringify(boardsData)).map(board => {
        board.headers = board.columns.map(c => ({ id: c.id, title: c.title, wip_limit: c.wip_limit, policy: c.policy }));

        board.swimlanes = [
            { id: 'Expedite', title: 'Expedite (Urgent)', color: 'text-red-400', border: 'border-red-500/30', badge: 'bg-red-500' },
            { id: 'Normal', title: 'Normal Flow', color: 'text-slate-200', border: 'border-[#303340]', badge: 'bg-purple-500' },
            { id: 'Low', title: 'Low Priority', color: 'text-slate-500', border: 'border-[#303340]', badge: 'bg-slate-500' }
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

const favoriteBoards = computed(() => localBoards.value.filter(b => b.is_favorite));
const toggleFavorite = (boardId) => { router.patch(route('boards.favorite', boardId), {}, { preserveScroll: true }); };

const getColumnCount = (board, columnId) => {
    let count = 0;
    board.swimlanes.forEach(swimlane => {
        count += (swimlane.cardsByColumn[columnId] || []).length;
    });
    return count;
};

const boardForm = useForm({ 
    title: '', 
    urgent_threshold_days: 7,
    todo_wip: null,
    doing_wip: 3,
    done_wip: null
});

const createBoard = () => { 
    boardForm.post(route('kanban.store'), { 
        onSuccess: () => { 
            boardForm.reset(); 
            boardForm.urgent_threshold_days = 7;
            boardForm.doing_wip = 3;
        } 
    }); 
};

// Traduzido para inglês
const deleteBoard = (boardId) => { if (confirm('Delete this project?')) router.delete(route('boards.destroy', boardId)); };

const checkMove = (evt) => {
    const toColumnId = parseInt(evt.to.dataset.columnId);
    if (toColumnId === parseInt(evt.from.dataset.columnId)) return true;
    let targetBoard = localBoards.value.find(b => b.headers.some(h => h.id === toColumnId));
    if(!targetBoard) return true;
    let targetHeader = targetBoard.headers.find(h => h.id === toColumnId);
    if (targetHeader && targetHeader.wip_limit !== null) {
        if (getColumnCount(targetBoard, toColumnId) >= targetHeader.wip_limit) return false; 
    }
    return true;
};

const newCardTitles = ref({});
const createCard = (columnId, priority) => {
    const key = `${columnId}-${priority}`;
    if (!newCardTitles.value[key]) return;
    router.post(route('cards.store', columnId), { title: newCardTitles.value[key], priority: priority }, { preserveScroll: true, onSuccess: () => { newCardTitles.value[key] = ''; } });
};

const editingCardId = ref(null); 
const editCardForm = useForm({ title: '', priority: 'Normal', is_blocked: false, block_reason: '' });

const startEditing = (card) => { 
    editingCardId.value = card.id; 
    editCardForm.title = card.title; 
    editCardForm.priority = card.priority || 'Normal'; 
    editCardForm.is_blocked = card.is_blocked ? true : false;
    editCardForm.block_reason = card.block_reason || '';
};

const updateCard = (cardId) => { 
    if (!editCardForm.is_blocked) editCardForm.block_reason = '';
    editCardForm.put(route('cards.update', cardId), { 
        preserveScroll: true, 
        onSuccess: () => { editingCardId.value = null; } 
    }); 
};

// Traduzido para inglês
const deleteCard = (cardId) => { if (confirm('Delete task?')) router.delete(route('cards.destroy', cardId)); };
const handleDragChange = (evt, columnId, priorityId) => { if (evt.added) { router.put(route('cards.update', evt.added.element.id), { column_id: columnId, priority: priorityId }, { preserveScroll: true }); } };

let automatorInterval;
onMounted(() => {
    automatorInterval = setInterval(() => {
        localBoards.value.forEach(board => {
            if (!board.urgent_threshold_days) return;
            const msLimit = board.urgent_threshold_days * 24 * 60 * 60 * 1000; 
            const now = new Date().getTime();

            board.columns.forEach(column => {
                column.cards.forEach(card => {
                    const isDoneColumn = column.title.toLowerCase().includes('done') || column.title.toLowerCase().includes('conclu');
                    if (card.priority !== 'Expedite' && !isDoneColumn && !card.is_blocked) {
                        const cardTime = new Date(card.updated_at).getTime();
                        if (now - cardTime > msLimit) {
                            router.put(route('cards.update', card.id), {
                                column_id: card.column_id,
                                priority: 'Expedite'
                            }, { preserveScroll: true, preserveState: true });
                        }
                    }
                });
            });
        });
    }, 5000);
});
onUnmounted(() => { clearInterval(automatorInterval); });
</script>

<template>
    <Head title="My Tasks" />

    <div class="h-screen w-full bg-[#1a1b21] font-sans text-slate-300 flex overflow-hidden">
        
        <aside class="w-64 bg-[#1e1f26] border-r border-[#303340] flex flex-col flex-shrink-0">
            <div class="h-20 flex items-center px-6 border-b border-[#303340]/50">
                <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center font-bold text-white mr-3 shadow-lg shadow-purple-500/30">
                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                </div>
                <span class="font-bold text-lg text-slate-100 tracking-wide">Workspace</span>
            </div>
            
            <div class="p-4 space-y-1 mt-2">
                <Link :href="route('overview')" 
                    :class="route().current('overview') ? 'bg-[#262833] text-purple-400' : 'text-slate-400 hover:bg-[#262833] hover:text-slate-100'" 
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition no-underline border-none outline-none focus:outline-none focus:ring-0">
                    Overview
                </Link>
                <Link :href="route('kanban.index')" 
                    :class="route().current('kanban.index') ? 'bg-[#262833] text-purple-400' : 'text-slate-400 hover:bg-[#262833] hover:text-slate-100'" 
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition no-underline border-none outline-none focus:outline-none focus:ring-0">
                    My Tasks
                </Link>
            </div>

            <div class="mt-4 px-4 flex-1 overflow-y-auto custom-scrollbar">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-3 px-3">Favorites</div>
                <div class="space-y-1">
                    <div v-if="favoriteBoards.length === 0" class="text-xs text-slate-600 italic px-3">No favorites yet</div>
                    <a v-for="board in favoriteBoards" :key="'fav-'+board.id" :href="'#board-' + board.id" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-slate-400 hover:bg-[#262833] hover:text-slate-100 transition no-underline group border-none outline-none focus:outline-none focus:ring-0">
                        <svg class="w-4 h-4 text-yellow-500 mr-2 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                        <span class="truncate">{{ board.title }}</span>
                    </a>
                </div>
            </div>

            <div class="p-4 border-t border-[#303340]/50">
                <Link :href="route('dashboard')" class="text-sm text-slate-400 hover:text-slate-200 no-underline border-none outline-none focus:outline-none focus:ring-0">Back to Dashboard</Link>
            </div>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#1a1b21]">
            <header class="h-20 flex items-center justify-between px-8 border-b border-[#303340] bg-[#1e1f26]">
                <h2 class="text-2xl font-bold text-slate-100 tracking-tight">My Tasks</h2>
                <div class="text-sm text-slate-400">Hello, {{ $page.props.auth.user.name }}</div>
            </header>

            <div class="flex-1 overflow-x-auto overflow-y-auto p-8 hide-scrollbar scroll-smooth">
                <div class="max-w-[1600px] pb-20">
                    
                    <div class="bg-[#262833] p-6 rounded-xl shadow-sm mb-10 border border-[#303340] max-w-4xl">
                        <form @submit.prevent="createBoard" class="space-y-4">
                            <div class="flex gap-4">
                                <input v-model="boardForm.title" type="text" placeholder="New project name..." class="flex-1 bg-[#1e1f26] border-transparent text-slate-200 focus:ring-purple-500 rounded-lg" required>
                                
                                <div class="flex items-center bg-[#1e1f26] rounded-lg px-2 border border-[#303340] focus-within:border-purple-500 transition-colors">
                                    <span class="text-slate-400 text-xs mr-2 ml-1">SLA (Days):</span>
                                    <button type="button" @click="boardForm.urgent_threshold_days > 1 ? boardForm.urgent_threshold_days-- : null" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-slate-200 hover:bg-[#303340] rounded transition">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                    </button>
                                    <input v-model="boardForm.urgent_threshold_days" type="number" min="1" class="w-10 bg-transparent border-none text-slate-200 text-sm focus:ring-0 px-0 text-center hide-arrows font-medium" required>
                                    <button type="button" @click="boardForm.urgent_threshold_days++" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-slate-200 hover:bg-[#303340] rounded transition">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    </button>
                                </div>

                                <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white font-semibold py-2 px-6 rounded-lg transition">Create Project</button>
                            </div>

                            <div class="flex items-center gap-6 pt-3 border-t border-[#303340]/50">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">WIP Limits:</span>
                                
                                <div class="flex gap-4">
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-slate-400">To Do:</label>
                                        <input v-model="boardForm.todo_wip" type="number" placeholder="∞" class="w-16 h-8 bg-[#1e1f26] border-transparent text-slate-200 text-xs rounded focus:ring-purple-500 hide-arrows text-center">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-slate-400">Doing:</label>
                                        <input v-model="boardForm.doing_wip" type="number" placeholder="∞" class="w-16 h-8 bg-[#1e1f26] border-transparent text-slate-200 text-xs rounded focus:ring-purple-500 hide-arrows text-center">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-slate-400">Done:</label>
                                        <input v-model="boardForm.done_wip" type="number" placeholder="∞" class="w-16 h-8 bg-[#1e1f26] border-transparent text-slate-200 text-xs rounded focus:ring-purple-500 hide-arrows text-center">
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-500 italic">* Leave empty for unlimited</p>
                            </div>
                        </form>
                    </div>

                    <div v-if="localBoards.length > 0" v-for="board in [...localBoards].sort((a,b) => b.is_favorite - a.is_favorite)" :key="board.id" class="mb-16 scroll-mt-8" :id="'board-' + board.id">
                        
                        <div class="flex items-center gap-6 mb-6">
                            <div class="flex items-center gap-4">
                                <button @click="toggleFavorite(board.id)" class="text-slate-600 hover:text-yellow-400 transition-colors focus:outline-none border-none" :class="{ 'text-yellow-400': board.is_favorite }">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                </button>
                                <h3 class="text-2xl font-bold text-slate-200">{{ board.title }}</h3>
                                <span class="bg-[#262833] border border-[#303340] text-purple-400 text-xs font-bold px-2.5 py-1 rounded-md">
                                    Automation: {{ board.urgent_threshold_days }} Days
                                </span>
                            </div>
                            <div class="h-px flex-1 bg-[#303340]"></div>
                            <button @click="deleteBoard(board.id)" class="text-slate-500 hover:text-red-400 text-sm font-medium focus:outline-none">Delete Project</button>
                        </div>
                        
                        <div class="inline-flex flex-col min-w-max bg-[#1e1f26]/30 p-6 rounded-2xl border border-[#303340]/50">
                            
                            <div class="flex gap-6 mb-4 pl-4">
                                <div v-for="header in board.headers" :key="'h-'+header.id" class="w-[300px] flex justify-between items-center pb-2 border-b-2 border-slate-700/50">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-slate-300 tracking-wide uppercase text-sm">{{ header.title }}</h4>
                                        <div v-if="header.policy" class="relative group flex items-center cursor-help">
                                            <svg class="w-4 h-4 text-purple-400/70 hover:text-purple-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-56 p-3 bg-[#262833] text-xs text-slate-300 rounded-lg shadow-xl border border-[#303340] z-50 whitespace-pre-wrap font-medium">
                                            
                                                    <div class="text-[10px] text-slate-500 uppercase tracking-wider mb-1">Column Policy</div>
                                                    {{ header.policy }}
                                                    <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-[#262833] border-b border-r border-[#303340] transform rotate-45"></div>
            
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <span v-if="header.wip_limit !== null" 
                                          class="text-xs font-bold px-2 py-0.5 rounded transition-colors duration-300 border"
                                          :class="getColumnCount(board, header.id) >= header.wip_limit ? 'bg-red-500/20 border-red-500/50 text-red-400 shadow-[0_0_8px_rgba(239,68,68,0.2)]' : 'bg-[#262833] border-slate-700 text-slate-400'">
                                          WIP: {{ getColumnCount(board, header.id) }} / {{ header.wip_limit }}
                                    </span>
                                </div>
                            </div>

                            <div v-for="swimlane in board.swimlanes" :key="swimlane.id" class="mb-6 last:mb-0 relative">
                                <div class="flex items-center gap-3 mb-3 sticky left-0 z-10">
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
                                                <div class="bg-[#303340] p-4 rounded-xl shadow-sm cursor-grab active:cursor-grabbing border group transition-all duration-500" 
                                                     :class="[
                                                        card.is_blocked ? 'border-dashed border-red-500/80 opacity-80 bg-red-950/20' : 
                                                        (card.priority === 'Expedite' ? 'border-red-500/50 shadow-[0_0_10px_rgba(239,68,68,0.1)]' : 'border-[#3e4252] hover:border-slate-400')
                                                     ]">
                                                    
                                                    <div v-if="editingCardId !== card.id">
                                                        <div class="w-8 h-1.5 rounded-full mb-3" :class="swimlane.badge"></div>
                                                        <div class="flex justify-between items-start">
                                                            <div>
                                                                <p class="text-sm font-medium text-slate-200" :class="card.is_blocked ? 'line-through text-slate-400' : ''">{{ card.title }}</p>
                                                                <div v-if="card.is_blocked" class="mt-3 flex items-start gap-1.5 bg-red-500/10 p-2 rounded border border-red-500/20">
                                                                    <svg class="w-3.5 h-3.5 text-red-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                                    <p class="text-xs text-red-300">{{ card.block_reason || 'Blocked (No reason provided)' }}</p>
                                                                </div>
                                                            </div>

                                                            <div class="flex gap-2 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <button @click="startEditing(card)" class="text-slate-400 hover:text-purple-400 focus:outline-none"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                                                                <button @click="deleteCard(card.id)" class="text-slate-400 hover:text-red-400 focus:outline-none"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div v-else>
                                                        <form @submit.prevent="updateCard(card.id)" class="space-y-3">
                                                            <input v-model="editCardForm.title" type="text" class="w-full text-sm bg-[#1e1f26] border-transparent rounded text-slate-200 focus:ring-purple-500">
                                                            <select v-model="editCardForm.priority" class="w-full text-sm bg-[#1e1f26] border-transparent rounded text-slate-300 focus:ring-purple-500">
                                                                <option value="Expedite">Expedite</option>
                                                                <option value="Normal">Normal</option>
                                                                <option value="Low">Low</option>
                                                            </select>
                                                            
                                                            <div class="bg-[#1e1f26] p-2 rounded border border-transparent focus-within:border-red-500/50">
                                                                <label class="flex items-center gap-2 cursor-pointer mb-2">
                                                                    <input type="checkbox" v-model="editCardForm.is_blocked" class="rounded bg-[#262833] border-slate-600 text-red-500 focus:ring-red-500 focus:outline-none">
                                                                    <span class="text-xs text-slate-400 font-medium">Mark as Blocked</span>
                                                                </label>
                                                                <input v-if="editCardForm.is_blocked" v-model="editCardForm.block_reason" type="text" placeholder="Reason for block..." class="w-full text-xs bg-[#262833] border-transparent rounded text-slate-200 focus:ring-red-500 py-1.5" required>
                                                            </div>

                                                            <div class="flex gap-2">
                                                                <button type="submit" class="bg-purple-600 text-white font-medium text-xs px-3 py-1.5 rounded hover:bg-purple-500 transition-colors">Save</button>
                                                                <button type="button" @click="editingCardId = null" class="text-slate-400 text-xs px-3 py-1.5 hover:bg-[#303340] rounded focus:outline-none">Cancel</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </template>
                                        </draggable>

                                        <form v-if="!header.title.toLowerCase().includes('done')" @submit.prevent="createCard(header.id, swimlane.id)" class="mt-1">
                                            <input v-model="newCardTitles[`${header.id}-${swimlane.id}`]" type="text" placeholder="+ Add task..." class="w-full bg-transparent border-transparent text-slate-500 text-sm focus:ring-0 px-2 py-1 hover:bg-[#303340]/50 rounded transition-colors">
                                            <button v-if="newCardTitles[`${header.id}-${swimlane.id}`]" type="submit" class="hidden"></button>
                                        </form>

                                        <div v-else-if="swimlane.cardsByColumn[header.id].length === 0" class="mt-1 px-2 py-1 text-slate-600 text-[11px] italic pointer-events-none select-none">
                                            Tasks can only be moved here
                                        </div>
                                        
                                    </div>
                                </div>
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
html { scroll-behavior: smooth; }

.hide-arrows::-webkit-outer-spin-button,
.hide-arrows::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.hide-arrows[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>