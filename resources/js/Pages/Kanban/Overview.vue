<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    boards: Array,
    priorityData: Array
});

const favoriteBoards = computed(() => props.boards.filter(b => b.is_favorite));

const priorityOptions = {
    chart: { type: 'donut', background: 'transparent' },
    labels: props.priorityData.map(d => d.priority || 'Normal'),
    colors: props.priorityData.map(d => {
        if (d.priority === 'Expedite') return '#ef4444'; 
        if (d.priority === 'Low') return '#64748b';      
        return '#a855f7';           
    }),
    theme: { mode: 'dark' },
    stroke: { show: true, colors: ['#1e1f26'], width: 2 },
    dataLabels: { enabled: false },
    legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
    title: { text: 'Tasks by Priority', style: { color: '#94a3b8', fontSize: '13px' } }
};
const prioritySeries = props.priorityData.map(d => d.count);
</script>

<template>
    <Head title="Analytics Overview" />

    <div class="min-h-screen bg-[#1a1b21] text-slate-200 flex">

        <aside class="w-64 bg-[#1e1f26] border-r border-[#303340] flex flex-col flex-shrink-0">
            <div class="h-20 flex items-center px-6 border-b border-[#303340]/50">
                <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center font-bold text-white mr-3 shadow-lg shadow-purple-500/30">
                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                </div>
                <span class="font-bold text-lg text-slate-100 tracking-wide">Workspace</span>
            </div>
            
            <div class="p-4 space-y-1 mt-2">
                <Link :href="route('overview')" :class="route().current('overview') ? 'bg-[#262833] text-purple-400' : 'text-slate-400 hover:bg-[#262833] hover:text-slate-100'" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition no-underline border-none outline-none focus:outline-none focus:ring-0">Overview</Link>
                <Link :href="route('kanban.index')" :class="route().current('kanban.index') ? 'bg-[#262833] text-purple-400' : 'text-slate-400 hover:bg-[#262833] hover:text-slate-100'" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition no-underline border-none outline-none focus:outline-none focus:ring-0">My Tasks</Link>
            </div>

            <div class="mt-4 px-4 flex-1 overflow-y-auto custom-scrollbar">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-3 px-3">Favorites</div>
                <div class="space-y-1">
                    <div v-if="favoriteBoards.length === 0" class="text-xs text-slate-600 italic px-3">No favorites yet</div>
                    <Link v-for="board in favoriteBoards" :key="'fav-'+board.id" 
                        :href="route('kanban.index') + '#board-' + board.id" 
                        class="flex items-center px-3 py-2 text-sm font-medium rounded-lg text-slate-400 hover:bg-[#262833] hover:text-slate-100 transition no-underline group border-none outline-none focus:outline-none focus:ring-0">
                        <svg class="w-4 h-4 text-yellow-500 mr-2 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                        <span class="truncate">{{ board.title }}</span>
                    </Link>
                </div>
            </div>

            <div class="p-4 border-t border-[#303340]/50">
                <Link :href="route('dashboard')" class="text-sm text-slate-400 hover:text-slate-200 no-underline border-none outline-none focus:outline-none focus:ring-0">Back to Dashboard</Link>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
            <div class="max-w-[1600px] mx-auto">
                <header class="mb-10">
                    <h1 class="text-3xl font-bold tracking-tight">Analytics Dashboard</h1>
                    <p class="text-slate-500 mt-2">Track the health and performance of your projects.</p>
                </header>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    <div class="bg-[#1e1f26] p-6 rounded-2xl border border-[#303340] shadow-sm flex items-center justify-center">
                        <VueApexCharts v-if="prioritySeries.length > 0" width="100%" height="300" :options="priorityOptions" :series="prioritySeries" />
                        <div v-else class="text-slate-500 text-sm italic">No data available</div>
                    </div>
                </div>

                <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <span class="w-2 h-6 bg-purple-500 rounded-full"></span>
                    My Projects
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="board in boards" :key="board.id" class="bg-[#1e1f26] p-6 rounded-2xl border border-[#303340] hover:border-purple-500/50 transition-all group">
                        <div class="flex justify-between items-start mb-6">
                            <h3 class="font-bold text-lg group-hover:text-purple-400 transition-colors">{{ board.title }}</h3>
                            <div class="text-[10px] font-bold px-2 py-1 rounded bg-purple-500/10 text-purple-400 uppercase tracking-widest border border-purple-500/20">
                                {{ board.stats.progress }}%
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="w-full bg-[#262833] h-1.5 rounded-full overflow-hidden">
                                <div class="bg-purple-500 h-full transition-all duration-1000" :style="{ width: board.stats.progress + '%' }"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 pt-2">
                                <div class="text-center">
                                    <div class="text-xs text-slate-500 uppercase font-bold mb-1">Total</div>
                                    <div class="text-slate-200 font-mono">{{ board.stats.total }}</div>
                                </div>
                                <div class="text-center border-x border-slate-700/50">
                                    <div class="text-xs text-slate-500 uppercase font-bold mb-1">Done</div>
                                    <div class="text-emerald-400 font-mono">{{ board.stats.completed }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-slate-500 uppercase font-bold mb-1">Left</div>
                                    <div class="text-amber-400 font-mono">{{ board.stats.pending }}</div>
                                </div>
                            </div>
                        </div>

                        <Link :href="route('kanban.index') + '#board-' + board.id" class="mt-6 w-full flex items-center justify-center gap-2 py-2.5 bg-[#262833] hover:bg-purple-600 text-sm font-bold rounded-xl transition-all text-slate-300 hover:text-white">
                            Open Board
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #303340; border-radius: 10px; }
</style>