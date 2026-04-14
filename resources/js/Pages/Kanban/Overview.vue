<script setup>
import { Head, Link } from '@inertiajs/vue3';
import apexchart from "vue3-apexcharts";

defineProps({
    boards: Array,
});

const getChartOptions = () => {
    return {
        chart: { type: 'donut', background: 'transparent' },
        colors: ['#6366f1', '#334155'], 
        labels: ['Completed', 'Pending'],
        stroke: { show: true, colors: ['#262833'], width: 2 },
        legend: { show: false }, 
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        name: { show: false },
                        value: { show: true, fontSize: '24px', fontWeight: 'bold', color: '#f1f5f9' },
                        total: { show: true, label: 'Total', color: '#94a3b8' }
                    }
                }
            }
        }
    };
};
</script>

<template>
    <Head title="Overview" />

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
                    
                    <Link :href="route('overview')" :class="route().current('overview') ? 'bg-[#262833] text-slate-100 shadow-sm border border-slate-700/50' : 'text-slate-400 hover:text-slate-200 hover:bg-[#262833]'" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition group">
                        <svg :class="route().current('overview') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300'" class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        Overview
                    </Link>
                    
                    <Link :href="route('kanban.index')" :class="route().current('kanban.index') ? 'bg-[#262833] text-slate-100 shadow-sm border border-slate-700/50' : 'text-slate-400 hover:text-slate-200 hover:bg-[#262833]'" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition group">
                        <svg :class="route().current('kanban.index') ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300'" class="w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        My Tasks
                    </Link>
                </div>
            </div>
            <div class="p-4 border-t border-[#303340]/50">
                <Link :href="route('dashboard')" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-400 hover:text-slate-200 hover:bg-[#262833] rounded-lg transition group">
                    <svg class="w-4 h-4 mr-3 text-slate-500 group-hover:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Back to Dashboard
                </Link>
            </div>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-y-auto bg-[#1a1b21]">
            <header class="h-20 flex items-center justify-between px-8 border-b border-[#303340] bg-[#1e1f26]">
                <h2 class="text-2xl font-bold text-slate-100 tracking-tight">Overview</h2>
                <div class="flex items-center gap-4">
                    <div class="text-sm text-slate-400 font-medium">Hello, {{ $page.props.auth.user.name }}</div>
                    <div class="w-9 h-9 rounded-full bg-indigo-900 border border-indigo-500/50 flex items-center justify-center text-indigo-300 font-bold">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                </div>
            </header>

            <div class="p-8">
                <div class="max-w-[1600px] mx-auto">
                    <header class="mb-10">
                        <h2 class="text-3xl font-bold text-slate-100 mb-2">Projects Overview</h2>
                        <p class="text-slate-500">Track individual progress across all your boards.</p>
                    </header>

                    <div v-if="boards.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        <div v-for="board in boards" :key="board.id" class="bg-[#262833] p-6 rounded-2xl border border-[#303340] flex flex-col shadow-sm">
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-slate-100 truncate" :title="board.title">{{ board.title }}</h3>
                                <p class="text-sm text-slate-500">{{ board.columns.length }} columns &bull; {{ board.stats.total }} tasks</p>
                            </div>
                            
                            <div class="flex-1 flex items-center justify-center -ml-4">
                                <apexchart v-if="board.stats.total > 0" width="280" type="donut" :options="getChartOptions()" :series="[board.stats.completed, board.stats.pending]"></apexchart>
                                <div v-else class="text-slate-500 text-sm py-10">No tasks created yet.</div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-[#303340] flex justify-between text-sm font-medium">
                                <div class="flex items-center">
                                    <span class="w-3 h-3 rounded-full bg-indigo-500 mr-2"></span>
                                    <span class="text-slate-300">{{ board.stats.completed }} Completed</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-3 h-3 rounded-full bg-slate-600 mr-2"></span>
                                    <span class="text-slate-400">{{ board.stats.pending }} Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-32 bg-[#262833] rounded-2xl border border-[#303340]">
                        <p class="text-slate-500 text-lg">You don't have any projects yet.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>