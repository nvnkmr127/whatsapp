<div id="automation-builder-wrapper">
    <div class="h-full flex flex-col bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100"
        x-data="flowBuilder">

        <!-- Alpine Logic -->
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('flowBuilder', () => ({
                    nodes: @entangle('nodes'),
                    edges: @entangle('edges'),
                    scale: 1,
                    panX: 0,
                    panY: 0,
                    isPanning: false,
                    panStart: { x: 0, y: 0 },
                    selectedId: null,
                    selectedEdgeIndex: null,
                    animationOffset: 0,
                    availableTags: @entangle('availableTags'),
                    debugLogs: @entangle('debugLogs'),
                    validationIssues: @entangle('validationIssues'),
                    stepMetadata: @entangle('stepMetadata'),

                    get nodesArray() {
                        return Array.isArray(this.nodes) ? this.nodes : Object.values(this.nodes || {});
                    },

                    get edgesArray() {
                        return Array.isArray(this.edges) ? this.edges : Object.values(this.edges || {});
                    },

                    get selectedNode() {
                        return this.nodesArray.find(n => n.id === this.selectedId);
                    },

                    getIssue(nodeId) {
                        if (!this.validationIssues) return null;
                        return this.validationIssues.find(issue => issue.node_id === nodeId);
                    },

                    hasError(nodeId) {
                        const issue = this.getIssue(nodeId);
                        return issue && issue.level === 'error';
                    },

                    hasWarning(nodeId) {
                        const issue = this.getIssue(nodeId);
                        return issue && issue.level === 'warning';
                    },

                    getFieldError(fieldName) {
                        if (!this.selectedId || !this.validationIssues) return null;
                        return this.validationIssues.find(issue => issue.node_id === this.selectedId && issue.field === fieldName);
                    },

                    focusField(fieldName) {
                        this.$nextTick(() => {
                            const el = document.querySelector(`[wire\\:model\\.blur="${fieldName}"], [wire\\:model\\.live="${fieldName}"]`);
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                el.focus();
                                el.classList.add('ring-4', 'ring-wa-teal/50', 'bg-wa-teal/5');
                                setTimeout(() => {
                                    el.classList.remove('ring-4', 'ring-wa-teal/50', 'bg-wa-teal/5');
                                }, 2000);
                            }
                        });
                    },

                    initTribute() {
                        this.$nextTick(() => {
                            if (typeof window.Tribute === 'undefined') return;

                            // Convert availableTags to Tribute format
                            // Assuming tags have 'name' property. 
                            // We might want other vars too? For now just tags or specific vars.
                            // Let's us a fast reliable list.
                            const tags = this.availableTags ? this.availableTags.map(t => ({ key: t.name, value: t.name })) : [];
                            const vars = [
                                { key: 'contact.name', value: 'contact.name' },
                                { key: 'contact.phone', value: 'contact.phone' },
                                { key: 'contact.email', value: 'contact.email' },
                                ...tags
                            ];

                            let tribute = new window.Tribute({
                                trigger: '@',
                                values: vars,
                                selectTemplate: function (item) {
                                    return '{{' + item.original.value + '}}';
                                }
                            });

                            document.querySelectorAll('.mentionable').forEach((el) => {
                                if (!el.hasAttribute('data-tribute')) {
                                    tribute.attach(el);
                                    el.setAttribute('data-tribute', 'true');
                                }
                            });
                        });
                    },

                    init() {
                        console.log('--- FLOW BUILDER INIT ---');
                        const canvas = this.$refs.canvas;
                        this.ctx = canvas.getContext('2d');

                        // Animation Loop
                        const animate = () => {
                            this.animationOffset = (this.animationOffset - 1) % 40; // Speed of animation
                            this.updateCanvas();
                            requestAnimationFrame(animate);
                        };
                        requestAnimationFrame(animate);

                        this.$watch('nodes', (val) => {
                            this.updateCanvas();
                            if (this.$wire.debugMode) {
                                console.log('--- NODES CHANGED ---', JSON.parse(JSON.stringify(val)));
                            }
                        });
                        this.$watch('edges', (val) => {
                            this.updateCanvas();
                            if (this.$wire.debugMode) {
                                console.log('--- EDGES CHANGED ---', JSON.parse(JSON.stringify(val)));
                            }
                        });
                    },

                    // Removed refreshAlpine() as entangle handles sync

                    startPan(e) {
                        if (e.target.id === 'canvas-container' || e.target.id === 'canvas') {
                            this.isPanning = true;
                            this.panStart = { x: e.clientX - this.panX, y: e.clientY - this.panY };
                        }
                    },
                    pan(e) {
                        if (!this.isPanning) return;
                        this.panX = e.clientX - this.panStart.x;
                        this.panY = e.clientY - this.panStart.y;
                    },
                    endPan() {
                        this.isPanning = false;
                    },
                    zoom(e) {
                        e.preventDefault();
                        const delta = -e.deltaY * 0.001;
                        this.scale = Math.min(Math.max(0.5, this.scale + delta), 2);
                    },
                    deselectAll() {
                        this.selectedId = null;
                        this.selectedEdgeIndex = null;
                        this.$wire.selectNode(null);
                    },
                    deleteNode(id) {
                        if (confirm('Are you sure you want to delete this node?')) {
                            this.selectedId = null;
                            this.$wire.deleteNode(id);
                        }
                    },

                    updateCanvas() {
                        if (!this.$refs.canvas) return;
                        const canvas = this.$refs.canvas;
                        if (canvas.width !== 10000) canvas.width = 10000;
                        if (canvas.height !== 10000) canvas.height = 10000;

                        this.ctx.clearRect(0, 0, canvas.width, canvas.height);

                        // Safety check for edges
                        if (!this.edges) return;

                        if (!this.edges) return;

                        const edges = this.edgesArray;
                        const nodes = this.nodesArray;

                        edges.forEach((edge, index) => {
                            const source = nodes.find(n => n.id === edge.source);
                            const target = nodes.find(n => n.id === edge.target);

                            if (source && target) {
                                const startX = source.x + 288 + 16 + 5000;
                                const startY = source.y + 48 + 5000;


                                const endX = target.x - 16 + 5000;
                                const endY = target.y + 48 + 5000;

                                this.ctx.beginPath();
                                this.ctx.moveTo(startX, startY);
                                const cpDist = Math.abs(endX - startX) * 0.5 + 50;
                                this.ctx.bezierCurveTo(startX + cpDist, startY, endX - cpDist, endY, endX, endY);

                                const isLoop = this.stepMetadata?.edges && this.stepMetadata.edges[index]?.isLoop;
                                if (isLoop) {
                                    this.ctx.strokeStyle = (this.selectedEdgeIndex === index) ? '#f59e0b' : '#fbbf24';
                                    this.ctx.setLineDash([5, 5]);
                                } else {
                                    this.ctx.strokeStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#94a3b8';
                                    this.ctx.setLineDash([]);
                                }
                                this.ctx.lineWidth = (this.selectedEdgeIndex === index) ? 4 : 3;

                                // ANIMATION
                                if (this.selectedEdgeIndex === index) {
                                    if (!isLoop) this.ctx.setLineDash([10, 5]);
                                    this.ctx.lineDashOffset = this.animationOffset;
                                }

                                this.ctx.stroke();

                                // Reset dash for text background
                                this.ctx.setLineDash([]);

                                // Label on edge
                                const midX = (startX + endX) / 2;
                                const midY = (startY + endY) / 2;

                                // Determine text from condition or fallback
                                let text = edge.condition;
                                if (!text) {
                                    if (source.type === 'condition') text = 'Else';
                                    else if (source.type === 'interactive_button' || source.type === 'interactive_list') text = 'Selection';
                                    else text = 'Next';
                                }

                                this.ctx.font = 'bold 11px Inter, sans-serif';
                                const width = this.ctx.measureText(text).width + 16;

                                // Draw badge
                                this.ctx.fillStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#ffffff';
                                this.ctx.strokeStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#cbd5e1';
                                this.ctx.lineWidth = 1;
                                this.ctx.beginPath();
                                this.ctx.roundRect(midX - width / 2, midY - 10, width, 20, 10);
                                this.ctx.fill();
                                this.ctx.stroke();

                                // Draw text
                                this.ctx.fillStyle = (this.selectedEdgeIndex === index) ? '#ffffff' : '#64748b';
                                this.ctx.textAlign = 'center';
                                this.ctx.textBaseline = 'middle';
                                this.ctx.fillText(text, midX, midY);
                            }
                        });

                        if (this.drawing && this.connectSourceId) {
                            const source = this.nodes.find(n => n.id === this.connectSourceId);
                            if (source) {
                                const startX = source.x + 288 + 16 + 5000;
                                const startY = source.y + 48 + 5000;

                                const mouseX_in_Canvas = (this.mouse.x - this.panX) / this.scale;
                                const mouseY_in_Canvas = (this.mouse.y - this.panY) / this.scale;

                                const targetX = mouseX_in_Canvas + 5000;
                                const targetY = mouseY_in_Canvas + 5000;

                                this.ctx.beginPath();
                                this.ctx.moveTo(startX, startY);
                                const cpDist = Math.abs(targetX - startX) * 0.5;
                                this.ctx.bezierCurveTo(startX + cpDist, startY, targetX - cpDist, targetY, targetX, targetY);

                                this.ctx.strokeStyle = '#25D366';
                                this.ctx.setLineDash([5, 5]);
                                this.ctx.lineWidth = 2;
                                this.ctx.stroke();
                                this.ctx.setLineDash([]);
                            }
                        }
                    },

                    startDrag(event, node) {
                        if (!node) return;
                        const id = node.id;
                        this.draggingNodeId = id;
                        this.selectedId = id;
                        this.$wire.selectNode(id);
                        this.selectedEdgeIndex = null;

                        this.dragStartMouse = { x: event.clientX, y: event.clientY };
                        this.dragStartNode = { x: node.x, y: node.y };

                        const moveHandler = (e) => {
                            if (!this.draggingNodeId) return;
                            const dx = (e.clientX - this.dragStartMouse.x) / this.scale;
                            const dy = (e.clientY - this.dragStartMouse.y) / this.scale;
                            const node = this.nodesArray.find(n => n.id === this.draggingNodeId);
                            if (node) {
                                node.x = this.dragStartNode.x + dx;
                                node.y = this.dragStartNode.y + dy;
                            }
                        };
                        const upHandler = () => {
                            if (this.draggingNodeId) {
                                const node = this.nodesArray.find(n => n.id === this.draggingNodeId);
                                if (node) {
                                    this.$wire.updateNodePosition(node.id, node.x, node.y);
                                    // entangle will eventually sync back, but direct update is safer for instant UI
                                }
                            }
                            this.draggingNodeId = null;
                            document.removeEventListener('mousemove', moveHandler);
                            document.removeEventListener('mouseup', upHandler);
                        };
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', upHandler);
                    },

                    startConnect(event, id) {
                        this.drawing = true;
                        this.connectSourceId = id;
                        const container = document.getElementById('canvas-container');
                        const rect = container.getBoundingClientRect();
                        this.mouse = { x: event.clientX - rect.left, y: event.clientY - rect.top };

                        const onMouseMove = (e) => {
                            const rect = container.getBoundingClientRect();
                            this.mouse = { x: e.clientX - rect.left, y: e.clientY - rect.top };
                        };
                        const onMouseUp = () => {
                            this.drawing = false;
                            this.connectSourceId = null;
                            document.removeEventListener('mousemove', onMouseMove);
                            document.removeEventListener('mouseup', onMouseUp);
                        };
                        document.addEventListener('mousemove', onMouseMove);
                        document.addEventListener('mouseup', onMouseUp);
                    },

                    endConnect(targetId) {
                        if (this.drawing && this.connectSourceId && this.connectSourceId !== targetId) {
                            this.edges.push({ source: this.connectSourceId, target: targetId, condition: '' });
                            this.$wire.addEdge(this.connectSourceId, targetId); // syncs to backend too
                        }
                        this.drawing = false;
                    }
                }))
            });
        </script>

        <!-- Top Toolbar -->
        <div
            class="h-16 flex-none bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 z-50">
            <div class="flex items-center gap-4">
                <a href="{{ route('automations.index') }}"
                    class="p-2 -ml-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    title="Back to Automations">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700"></div>
                <div class="flex flex-col">
                    <input type="text" wire:model.blur="name"
                        class="bg-transparent border-0 p-0 text-sm font-bold text-slate-800 dark:text-white leading-tight focus:ring-0 placeholder-slate-400"
                        placeholder="Untitled Automation">
                    <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">
                        {{ $triggerType === 'keyword' ? 'Keywords: ' . implode(', ', $triggerConfig['keywords'] ?? []) : ucfirst(str_replace('_', ' ', $triggerType)) }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">


                <button type="button" wire:click.prevent="save"
                    class="text-xs font-bold px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">
                    Save as Draft
                </button>

                <button type="button" wire:click.prevent="publish"
                    class="text-xs font-bold px-5 py-2 rounded-xl flex items-center gap-2 transition shadow-lg outline-none focus:ring-2 focus:ring-offset-2"
                    :class="!$wire.isActivatable ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-rose-500/20 focus:ring-rose-500' : 'bg-wa-teal hover:bg-wa-dark text-white shadow-wa-teal/20 focus:ring-wa-teal'">

                    <template x-if="!$wire.isActivatable">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="$wire.isActivatable">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </template>

                    <span x-text="!$wire.isActivatable ? 'Fix Errors to Publish' : 'Publish Flow'"></span>
                </button>
            </div>
        </div>

        <!-- Workspace -->
        <div class="flex-1 flex overflow-hidden relative">

            <!-- Preflight Checklist Sidebar -->
            <div x-show="validationIssues && validationIssues.length > 0"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-8 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                class="absolute bottom-6 left-6 z-[60] w-80 max-h-[400px] flex flex-col bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">

                <div
                    class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="relative flex h-2 w-2"
                            x-show="$wire.validationIssues.some(i => i.level === 'error')">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                        </div>
                        <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest">Preflight Checklist</h4>
                    </div>
                    <span
                        class="text-[10px] font-bold bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded-full text-slate-600 dark:text-slate-300"
                        x-text="validationIssues.length"></span>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                    <template x-for="issue in validationIssues" :key="JSON.stringify(issue)">
                        <div @click="if(issue.node_id) { selectedId = issue.node_id; $wire.selectNode(issue.node_id); panX = -nodesArray.find(n => n.id === issue.node_id).x * scale + (window.innerWidth / 2) - 150; panY = -nodesArray.find(n => n.id === issue.node_id).y * scale + (window.innerHeight / 2); if(issue.field) focusField(issue.field);
                           }"
                          class="group p-3 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-700 hover:bg-white dark:hover:bg-slate-800 transition-all cursor-pointer overflow-hidden">
                         <div class="flex gap-3">
                             <div class="mt-0.5">
                                 <svg x-show="issue.level === 'error'" class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                 <svg x-show="issue.level === 'warning'" class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                             </div>
                             <div class="flex-1">
                                 <p class="text-[11px] font-bold text-slate-700 dark:text-slate-200 leading-tight group-hover:text-wa-teal transition-colors" x-text="issue.message"></p>
                                 <p x-show="issue.node_id" class="text-[9px] text-slate-400 mt-1 uppercase font-bold tracking-tighter" x-text="'Issue in: ' + (nodesArray.find(n => n.id === issue.node_id)?.data?.label || 'Node ' + issue.node_id.substring(0,6))"></p>
                             </div>
                         </div>
                     </div>
                 </template>
             </div>
        </div>

        <div class="absolute top-4 left-1/2 -translate-x-1/2 z-[60] w-full max-w-lg space-y-2 pointer-events-none">
            @if (session()->has('success'))
                <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 pointer-events-auto mx-4"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="font-bold text-sm">{{ session('success') }}</span>
                </div>
            @endif
        </div>

        <!-- Left Sidebar: Component Palette -->
        <div class="w-72 flex-none bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col z-40 transition-all duration-300"
             :class="{'w-72': true, 'w-0 opacity-0 overflow-hidden': false}">
            
            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" placeholder="Search components..."
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs py-2.5 pl-9 pr-3 focus:ring-2 focus:ring-wa-teal focus:border-transparent transition-shadow">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6 custom-scrollbar">
                 <!-- Component Groups -->
                 @foreach([
                        'Messages' => [
                            ['type' => 'text', 'label' => 'Text Message', 'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                            ['type' => 'image', 'label' => 'Image', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
                            ['type' => 'video', 'label' => 'Video', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'text-pink-500', 'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                            ['type' => 'audio', 'label' => 'Audio', 'icon' => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z', 'color' => 'text-cyan-500', 'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                            ['type' => 'file', 'label' => 'Document', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100 dark:bg-slate-800'],
                            ['type' => 'template', 'label' => 'WhatsApp Template', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'text-green-500', 'bg' => 'bg-green-50 dark:bg-green-900/20'],
                            ['type' => 'interactive_button', 'label' => 'Reply Buttons', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                            ['type' => 'interactive_list', 'label' => 'List Menu', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                            ['type' => 'carousel', 'label' => 'Carousel', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
                            ['type' => 'send_flow', 'label' => 'Send Flow', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                            ['type' => 'location_request', 'label' => 'Location Request', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'color' => 'text-red-500', 'bg' => 'bg-red-50 dark:bg-red-900/20'],
                            ['type' => 'contact', 'label' => 'Send Contact', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                        ],
                        'Logic & Flow' => [
                            ['type' => 'condition', 'label' => 'Condition', 'icon' => 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50 dark:bg-amber-900/20'],
                            ['type' => 'delay', 'label' => 'Delay / Wait', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100 dark:bg-slate-800'],
                        ],
                        'Inputs' => [
                            ['type' => 'user_input', 'label' => 'Ask Question', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093', 'color' => 'text-cyan-500', 'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                        ],
                        'Integrations' => [
                            ['type' => 'openai', 'label' => 'AI Assistant', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                            ['type' => 'webhook', 'label' => 'Webhook', 'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9', 'color' => 'text-pink-500', 'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                            ['type' => 'crm_sync', 'label' => 'CRM Sync', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                        ]
                    ] as $group => $items)
                                            <div>
                                                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3 ml-1">{{ $group }}</h3>
                                                <div class="space-y-2">
                                                    @foreach($items as $item)
                                                        <button @click="$wire.addNode('{{ $item['type'] }}')"
                                                            class="w-full flex items-center gap-3 px-3 py-2.5 bg-white dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/50 hover:border-wa-teal/30 dark:hover:border-wa-teal/30 rounded-xl transition-all group shadow-sm hover:shadow-md hover:scale-[1.02]">
                                                            <div class="p-2 rounded-lg {{ $item['bg'] }} {{ $item['color'] }} transition-colors">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                                                                </svg>
                                                            </div>
                                                            <div class="flex flex-col items-start">
                                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-wa-teal transition-colors">{{ $item['label'] }}</span>
                                                            </div>
                                                            <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                                                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                            </div>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                 @endforeach
            </div>
        </div>

                <!-- Center: Infinite Canvas -->
        <div class="flex-1 bg-slate-50 dark:bg-slate-950 relative overflow-hidden cursor-grab active:cursor-grabbing" id="canvas-container"
             @mousedown="startPan($event)" @mousemove="pan($event)" @mouseup="endPan()" @mouseleave="endPan()" @wheel="zoom($event)">
             
             <!-- Canvas Controls -->
            <div class="absolute bottom-6 right-6 flex items-center gap-2 z-50">
                <div class="flex items-center bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-slate-200 dark:border-slate-800 p-1">
                    <button @click="scale = Math.min(scale + 0.1, 2)" 
                        class="p-2 text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md transition-colors" title="Zoom In">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </button>
                    <div class="w-px h-4 bg-slate-200 dark:bg-slate-700 mx-1"></div>
                    <button @click="scale = Math.max(scale - 0.1, 0.5)" 
                        class="p-2 text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md transition-colors" title="Zoom Out">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <div class="w-px h-4 bg-slate-200 dark:bg-slate-700 mx-1"></div>
                    <button @click="panX = 0; panY = 0; scale = 1" 
                        class="p-2 text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md transition-colors" title="Fit to Center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    </button>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg border border-slate-200 dark:border-slate-800 px-3 py-2 text-xs font-bold text-slate-500 font-mono">
                    <span x-text="Math.round(scale * 100) + '%'"></span>
                </div>
            </div>
            
                <!-- Canvas Grid -->
            <div id="canvas" class="absolute inset-0 origin-top-left" wire:ignore
                 :style="`transform: translate(${panX}px, ${panY}px) scale(${scale});`"
                 @click.self="deselectAll()">
                
                <!-- Expanded Grid Background -->
                <div class="absolute -top-[5000px] -left-[5000px] w-[10000px] h-[10000px] pointer-events-none opacity-[0.05] dark:opacity-[0.03]"
                     style="background-image: linear-gradient(to right, #64748b 1px, transparent 1px), linear-gradient(to bottom, #64748b 1px, transparent 1px); background-size: 40px 40px;">
                </div>

                <!-- Edges Layer (Corrected Z-Index and Dimensions) -->
                <canvas x-ref="canvas" width="10000" height="10000" class="absolute -top-[5000px] -left-[5000px] w-[10000px] h-[10000px] pointer-events-none z-[1]"></canvas>

                <!-- Nodes Layer -->
                <template x-for="node in nodes" :key="node.id">
                    <x-automations.node />
                </template>

            </div>
        </div>

        <!-- Debug JSON Overlay -->


        <!-- Right Sidebar: Properties & History -->
        <div class="w-80 flex-none bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 flex flex-col z-40 shadow-xl transition-all duration-300"
             :class="{'translate-x-0': true}">
            
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/20">
                <h3 class="font-black text-[10px] uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400" x-text="selectedId ? 'Node Properties' : (selectedEdgeIndex !== null ? 'Edge Properties' : 'Automation Summary')"></h3>
                <button x-show="selectedId || selectedEdgeIndex !== null" @click="deselectAll()" class="p-1 px-2 bg-slate-200 dark:bg-slate-700 rounded-lg text-[10px] font-black uppercase text-slate-600 dark:text-slate-400 hover:bg-wa-teal hover:text-white transition-all">
                    Done
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-8 custom-scrollbar">

                <!-- Flow Settings / Version History (Shown when nothing is selected) -->
                <div x-show="!selectedId && selectedEdgeIndex === null" class="space-y-8">
                    
                    <!-- Live Stats -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest">Active Status</h4>
                        <div class="p-4 rounded-2xl border flex items-center justify-between"
                             :class="$wire.isActivatable && $wire.automationId ? 'bg-emerald-50 border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-800/30' : 'bg-slate-50 border-slate-100 dark:bg-slate-800/30'">
                             <div class="flex items-center gap-3">
                                 <div class="w-3 h-3 rounded-full" :class="$wire.isActivatable && $wire.automationId ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></div>
                                 <span class="text-sm font-bold text-slate-700 dark:text-slate-200" x-text="$wire.automationId ? 'Status: Live' : 'Status: Draft'"></span>
                             </div>
                             <span class="text-[10px] font-black px-2 py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">v{{ $version }}</span>
                        </div>
                    </div>

                    <!-- Version History -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest">Version History</h4>
                        </div>
                        
                        <div class="space-y-4 relative before:absolute before:left-[17px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100 dark:before:bg-slate-800">
                            @forelse($publishLog as $log)
                                <div class="relative pl-12">
                                    <div class="absolute left-0 top-1 w-9 h-9 bg-white dark:bg-slate-900 rounded-xl border-2 border-slate-100 dark:border-slate-800 flex items-center justify-center z-10">
                                        <span class="text-[10px] font-black text-slate-500">v{{ $log['version'] }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200">{{ $log['note'] ?: 'No description provided.' }}</span>
                                        <span class="text-[9px] text-slate-400 mt-1 uppercase font-black tracking-tighter">
                                            {{ \Carbon\Carbon::parse($log['published_at'])->diffForHumans() }} by {{ $log['published_by'] }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8">
                                    <div class="inline-flex p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl mb-3">
                                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">No previous versions</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
                <div x-show="selectedId">
                    <template x-if="selectedNode">
                        <div class="space-y-6">

                            <!-- Node Validation Summary -->
                            <template x-if="validationIssues.filter(i => i.node_id === selectedId).length > 0">
                                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/30">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="p-1.5 bg-rose-500 rounded-lg text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                        <span class="text-xs font-black uppercase text-rose-600 dark:text-rose-400 tracking-wider">Attention Required</span>
                                    </div>
                                    <ul class="space-y-2">
                                        <template x-for="issue in validationIssues.filter(i => i.node_id === selectedId)" :key="JSON.stringify(issue)">
                                            <li class="flex items-start gap-2">
                                                <div class="w-1 h-1 rounded-full bg-rose-400 mt-1.5"></div>
                                                <p class="text-[11px] font-bold text-rose-700 dark:text-rose-300 leading-tight" x-text="issue.message"></p>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                            
                            <!-- Node Label Editor -->
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase italic">Identifying Label</label>
                                <input type="text" wire:model.live="nodeLabel" placeholder="Enter node name..."
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 transition-all"
                                    :class="getFieldError('nodeLabel') ? 'border-rose-500 ring-rose-500/20' : ''">
                                <p x-show="!getFieldError('nodeLabel')" class="text-[9px] text-slate-400 px-1">Internal use only, helps organize your flow.</p>
                                <p x-show="getFieldError('nodeLabel')" class="text-[10px] text-rose-500 font-bold px-1" x-text="getFieldError('nodeLabel')?.message"></p>
                            </div>

                            <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

                            <!-- Trigger Configuration -->
                            <div x-show="selectedNode.type === 'trigger'">
                                <div class="space-y-4">
                                     <!-- Trigger Type Dropdown -->
                                    <div class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Trigger Event</label>
                                        <select wire:model.live="triggerType" wire:change="updateNodeData"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                            <option value="keyword">Keyword/Regex Match</option>
                                            <option value="user_starts_conversation">User Starts Conversation</option>
                                            <option value="template_response">Template Response (Quick Reply)</option>
                                            <option value="template_selected">Template Selected</option>
                                            <option value="template_delivered">WhatsApp Template Delivered</option>
                                            <option value="contact_added">Contact Added</option>
                                            <option value="custom_field_updated">Custom Field Updated</option>
                                            <option value="tag_assigned">Tag Assigned</option>
                                            <option value="payment_capture">Payment Capture</option>
                                            <option value="order_received">Order Received</option>
                                        </select>
                                    </div>

                                    <!-- Keyword Match Config -->
                                    <div x-show="['keyword'].includes($wire.triggerType)" class="space-y-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                                        <div class="space-y-1">
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase">Keywords (Comma separated)</label>
                                            <!-- We bind to a specific sub-item of triggerConfig via livewire array notation, or handle json_encode manual if needed. 
                                                 Livewire supports array binding: triggerConfig.keywords 
                                                 But since it's an array of strings, we might need a textual input that splits on comma? 
                                                 Or just an array input. Let's assume standard textual input for now or tags. 
                                                 Actually, let's use a textarea for simple comma separation. -->
                                            
                                            <!-- Since triggerConfig is an array, we can't wire:model="triggerConfig.keywords" directly if it's meant to be an array in PHP? 
                                                 Let's assume we create a helper property "triggerKeywords" string in PHP or just handle it here. 
                                                 Actually, let's just stick to a text area and let the user type lines or commas. 
                                                 Wait, I should create a computed property or updated hook in PHP to parse this. 
                                                 For now, I'll bind to a temporary live property or use a simple hack.
                                                 
                                                 Plan: Bind to triggerConfig.keywords directly as array? No, HTML input doesn't support array. 
                                                 Better Plan: Use a text input and I'll add a 'updatedTriggerConfig' hook in PHP later if needed?
                                                 
                                                 Let's rely on Alpine or just use a simple text field that users enter "hi, hello" and we save it as ["hi", "hello"] in PHP updated hook.
                                                 Actually, for this step, let's assume the user edits `triggerConfig.keywords` as a string for now? No, PHP typed it as array.
                                                 
                                                 Let's use a dedicated method: `addTriggerKeyword` similar to headers. -->
                                        </div>
                                         <div class="space-y-2">
                                            @foreach($triggerConfig['keywords'] ?? [] as $index => $kw)
                                                <div class="flex items-center gap-2">
                                                    <input type="text" wire:model.live="triggerConfig.keywords.{{ $index }}" 
                                                        class="flex-1 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5"
                                                        placeholder="Keyword">
                                                    <button wire:click="removeTriggerKeyword({{ $index }})" class="text-slate-400 hover:text-rose-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                             <button wire:click="addTriggerKeyword"
                                                class="text-xs font-bold text-wa-teal hover:underline">+ Add Keyword</button>
                                        </div>
                                        
                                        <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                                            <span class="text-[10px] font-bold uppercase text-slate-500">Regex Mode</span>
                                            <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                                                <input type="checkbox" wire:model.live="triggerConfig.is_regex" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                                                <label class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-300 cursor-pointer"></label>
                                            </div>
                                        </div>
                                        <p class="text-[9px] text-slate-400">If enabled, keywords are treated as Regular Expressions.</p>
                                    </div>

                                    <!-- User Starts Conversation -->
                                    <div x-show="['user_starts_conversation'].includes($wire.triggerType)" class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 rounded-xl text-xs">
                                        This flow triggers when a user sends the first message or a message after 24h window expiry.
                                    </div>

                                     <!-- Template Selected / Response -->
                                    <div x-show="['template_selected', 'template_response'].includes($wire.triggerType)" class="space-y-4">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Select Template</label>
                                            <select wire:model.live="triggerConfig.template_name"
                                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                                <option value="">Choose a template...</option>
                                                @foreach($approvedTemplates as $tmpl)
                                                    <option value="{{ $tmpl['name'] }}">{{ $tmpl['name'] }} ({{ $tmpl['language'] }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div x-show="$wire.triggerType === 'template_response'" class="space-y-1">
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Button Text to Match</label>
                                            <input type="text" wire:model.blur="triggerConfig.button_text" placeholder="e.g. Yes, Interested"
                                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                            <p class="text-[10px] text-slate-400">The flow triggers when the user clicks a button with this exact text.</p>
                                        </div>

                                        <div x-show="$wire.triggerType === 'template_selected'" class="space-y-1">
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Button Payload (Optional)</label>
                                            <input type="text" wire:model.blur="triggerConfig.button_payload" placeholder="Specific button ID"
                                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                                        </div>
                                    </div>

                                    <!-- Template Delivered -->
                                    <div x-show="['template_delivered'].includes($wire.triggerType)" class="space-y-4">
                                        <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-800">
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                Triggers when a WhatsApp message template is successfully delivered to a contact.
                                            </p>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="block text-xs font-bold text-slate-500 uppercase">Select Whatsapp Template</label>
                                            <select wire:model.live="triggerConfig.template_name"
                                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                                <option value="">Choose a template...</option>
                                                @foreach($approvedTemplates as $tmpl)
                                                    <option value="{{ $tmpl['name'] }}">{{ $tmpl['name'] }} ({{ $tmpl['language'] }})</option>
                                                @endforeach
                                            </select>
                                            <p class="text-[10px] text-slate-400 mt-1">
                                                Triggers the flow when the selected whatsapp template is delivered.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Tag Assigned -->
                                    <div x-show="['tag_assigned'].includes($wire.triggerType)" class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Select Tag</label>
                                        <select wire:model.blur="triggerConfig.tag_name"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                            <option value="">Choose a tag...</option>
                                            @foreach($availableTags as $tag)
                                                <option value="{{ $tag['name'] }}">{{ $tag['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-[10px] text-slate-400 mt-1">
                                            Triggers the flow when this tag is assigned to a contact.
                                        </p>
                                    </div>

                                    <!-- Contact List Config (Corrected) -->
                                    <div x-show="['contact_added'].includes($wire.triggerType)" class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Contact List ID</label>
                                        <input type="text" wire:model.blur="triggerConfig.contact_list_id" placeholder="List ID (Optional)"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                                    </div>


                                    
                                    <!-- Custom Field / Payment / Order (Generic Placeholder) -->
                                    <div x-show="['custom_field_updated', 'payment_capture', 'order_received'].includes($wire.triggerType)" class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs text-slate-500">
                                        No additional configuration required for this trigger type.
                                    </div>
                                    
                                    <!-- START NODE ACTIONS -->
                                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                                        <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Start Actions</h4>
                                        
                                        <!-- Add Tags -->
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase">Add Tags</label>
                                                <button wire:click="addStartTag" class="text-[10px] uppercase font-bold text-wa-teal hover:underline">+ Add</button>
                                            </div>
                                            @foreach($triggerConfig['add_tags'] ?? [] as $index => $tag)
                                                <div class="flex items-center gap-2">
                                                    <select wire:model.blur="triggerConfig.add_tags.{{ $index }}" 
                                                        class="flex-1 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5 appearance-none">
                                                        <option value="">Select Tag...</option>
                                                        @foreach($availableTags as $availableTag)
                                                            <option value="{{ $availableTag['name'] }}">{{ $availableTag['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button wire:click="removeStartTag({{ $index }})" class="text-slate-400 hover:text-rose-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Remove Tags -->
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase">Remove Tags</label>
                                                <button wire:click="addRemoveTag" class="text-[10px] uppercase font-bold text-wa-teal hover:underline">+ Add</button>
                                            </div>
                                            @foreach($triggerConfig['remove_tags'] ?? [] as $index => $tag)
                                                <div class="flex items-center gap-2">
                                                     <select wire:model.blur="triggerConfig.remove_tags.{{ $index }}" 
                                                        class="flex-1 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5 appearance-none">
                                                        <option value="">Select Tag...</option>
                                                        @foreach($availableTags as $availableTag)
                                                            <option value="{{ $availableTag['name'] }}">{{ $availableTag['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button wire:click="removeRemoveTag({{ $index }})" class="text-slate-400 hover:text-rose-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>

                                        <!-- Webhook -->
                                        <div class="space-y-1">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Start Webhook URL</label>
                                            <input type="text" wire:model.blur="triggerConfig.webhook_url" placeholder="https://api.example.com/start"
                                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs">
                                        </div>
                                    </div>

                                </div>
                            </div>

                             <div class="space-y-4" x-show="selectedNode.type === 'template'">
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold text-slate-500 uppercase transition-colors" :class="getFieldError('nodeText') ? 'text-rose-500' : ''">Template Name</label>
                                    <select wire:model.blur="nodeText" wire:change="updatedNodeText($event.target.value)"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 transition-all shadow-sm"
                                        :class="getFieldError('nodeText') ? 'border-rose-500 ring-rose-500/20' : ''">
                                        <option value="">Select Template...</option>
                                        @foreach($approvedTemplates as $tmpl)
                                            <option value="{{ $tmpl['name'] }}">{{ $tmpl['name'] }} ({{ $tmpl['language'] }})</option>
                                        @endforeach
                                    </select>
                                    <p x-show="getFieldError('nodeText')" class="text-[10px] text-rose-500 font-bold px-1 mt-1" x-text="getFieldError('nodeText')?.message"></p>
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-bold text-slate-500 uppercase">Language Code</label>
                                    <input type="text" wire:model.blur="nodeLanguage" wire:change="updateNodeData"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold uppercase"
                                        placeholder="en">
                                </div>
                            </div>

                            <!-- Condition Logic -->
                            <div x-show="selectedNode.type === 'condition'">
                                <div class="space-y-4">
                                    <div class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Operator</label>
                                        <select wire:model.blur="nodeOperator" wire:change="updateNodeData"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                                            <option value="eq">Equals</option>
                                            <option value="neq">Not Equals</option>
                                            <option value="contains">Contains</option>
                                            <option value="gt">Greater Than</option>
                                            <option value="lt">Less Than</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Value to match</label>
                                        <input type="text" wire:model.blur="nodeText" wire:change="updateNodeData"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm"
                                            placeholder="Value">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- URL / Resource / Upload -->
                             <div class="space-y-4" x-show="['image', 'video', 'audio', 'file', 'webhook'].includes(selectedNode.type)">
                                
                                <!-- File Upload (Media Only) -->
                                <div x-show="['image', 'video', 'audio', 'file'].includes(selectedNode.type)">
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Upload File</label>
                                    <input type="file" wire:model="uploadFile"
                                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-wa-teal/10 file:text-wa-teal hover:file:bg-wa-teal/20"
                                        :accept="selectedNode.type === 'image' ? 'image/png,image/jpeg' : (selectedNode.type === 'video' ? 'video/mp4,video/3gpp' : (selectedNode.type === 'audio' ? 'audio/mpeg,audio/ogg,audio/wav' : '*/*'))"
                                    >
                                    <div wire:loading wire:target="uploadFile" class="text-xs text-wa-teal font-bold mt-1 animate-pulse">
                                        Uploading...
                                    </div>
                                    
                                    <div class="relative flex items-center gap-2 mt-4 mb-2">
                                        <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                                        <span class="flex-shrink-0 text-[10px] text-slate-400 font-bold uppercase">OR USE URL</span>
                                        <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                                    </div>
                                    
                                    <p class="text-[10px] text-slate-400 mt-1" x-text="
                                        selectedNode.type === 'image' ? 'Supported: JPEG, PNG' :
                                        (selectedNode.type === 'video' ? 'Supported: MP4, 3GPP' :
                                        (selectedNode.type === 'audio' ? 'Supported: AAC, MP4, MPEG, AMR, OGG' :
                                        'Supported: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT'))
                                    "></p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1 transition-colors" :class="getFieldError('nodeUrl') ? 'text-rose-500' : ''">Resource URL</label>
                                    <input type="text" wire:model.blur="nodeUrl" wire:change="updateNodeData"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 transition-all shadow-sm"
                                        :class="getFieldError('nodeUrl') ? 'border-rose-500 ring-rose-500/20' : ''"
                                        placeholder="https://... or ID">
                                    <p x-show="getFieldError('nodeUrl')" class="text-[10px] text-rose-500 font-bold px-1 mt-1" x-text="getFieldError('nodeUrl')?.message"></p>
                                </div>
                            </div>
                            
                            <!-- Text / Content / Caption -->
                            <div x-show="['text', 'interactive_button', 'interactive_list', 'user_input', 'openai', 'delay', 'image', 'video', 'audio', 'file'].includes(selectedNode.type)">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold text-slate-500 uppercase" x-text="
                                        selectedNode.type === 'user_input' ? 'Question' : 
                                        (selectedNode.type === 'openai' ? 'Prompt' : 
                                        (selectedNode.type === 'delay' ? 'Seconds' : 
                                        (['image', 'video', 'audio', 'file'].includes(selectedNode.type) ? 'Caption' : 'Message Text')))
                                    "></label>
                                    
                                    <button type="button" @click="const el = $el.closest('.space-y-6').querySelector('textarea'); el.value += '@{{'; el.dispatchEvent(new Event(&quot;input&quot;)); el.focus();"
                                        class="flex items-center gap-1 text-[10px] font-bold text-wa-teal bg-wa-teal/5 px-2 py-1 rounded-lg border border-wa-teal/20 hover:bg-wa-teal/10 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        Insert Variable
                                    </button>
                                </div>
                                
                                <div class="relative">
                                    <textarea wire:model.blur="nodeText" wire:change="updateNodeData" rows="6" @focus="initTribute"
                                        class="mentionable w-full bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 p-4 leading-relaxed transition-all shadow-sm"
                                        :class="getFieldError('nodeText') ? 'border-rose-500 ring-rose-500/20' : ''"
                                        :placeholder="selectedNode.type === 'openai' ? 'Enter system instructions...' : 'WhatsApp text message limit is 4096 characters.'"></textarea>
                                     <div class="absolute top-3 right-3 text-slate-400">
                                         <svg class="w-5 h-5 opacity-50" :class="getFieldError('nodeText') ? 'text-rose-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                     </div>
                                </div>
                                <p x-show="getFieldError('nodeText')" class="text-[10px] text-rose-500 font-bold px-1 mt-1" x-text="getFieldError('nodeText')?.message"></p>
                                <p class="text-[10px] text-slate-400 mt-1" x-show="!getFieldError('nodeText') && selectedNode.type !== 'text'">Markdown enabled. Use @{{variable}} for dynamic data.</p>
                            </div>
                            
                            <!-- Typing and Delay (Global) -->
                             <div x-show="['text', 'image', 'video', 'audio', 'file', 'template', 'interactive_button', 'interactive_list', 'location_request', 'contact'].includes(selectedNode.type)" 
                                  class="space-y-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                                 <!-- Typing Toggle -->
                                 <div class="flex items-center justify-between">
                                     <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Typing on display</span>
                                     <div class="relative inline-block w-10 align-middle select-none transition duration-200 ease-in">
                                        <input type="checkbox" wire:model.live="nodeTyping" wire:change="updateNodeData" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer"/>
                                        <label class="toggle-label block overflow-hidden h-5 rounded-full bg-slate-300 dark:bg-slate-700 cursor-pointer"></label>
                                    </div>
                                 </div>

                                 <!-- Delay Sliders -->
                                 <div>
                                     <label class="block text-xs font-bold text-slate-500 uppercase mb-4">Delay in reply</label>
                                     
                                     <!-- Seconds -->
                                     <div class="space-y-2 mb-4">
                                         <div class="flex justify-between text-[11px] font-bold text-slate-600 dark:text-slate-400">
                                             <span>sec: {{ $nodeDelaySeconds }}</span>
                                         </div>
                                         <input type="range" min="0" max="60" wire:model.live="nodeDelaySeconds" wire:change="updateNodeData" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-wa-teal">
                                     </div>

                                      <!-- Minutes -->
                                     <div class="space-y-2 mb-4">
                                         <div class="flex justify-between text-[11px] font-bold text-slate-600 dark:text-slate-400">
                                             <span>minutes: {{ $nodeDelayMinutes }}</span>
                                         </div>
                                         <input type="range" min="0" max="60" wire:model.live="nodeDelayMinutes" wire:change="updateNodeData" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-wa-teal">
                                     </div>

                                      <!-- Hours -->
                                     <div class="space-y-2">
                                         <div class="flex justify-between text-[11px] font-bold text-slate-600 dark:text-slate-400">
                                             <span>hours: {{ $nodeDelayHours }}</span>
                                         </div>
                                         <input type="range" min="0" max="24" wire:model.live="nodeDelayHours" wire:change="updateNodeData" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-wa-teal">
                                     </div>
                                 </div>
                             </div>

                            <!-- OpenAI Model -->
                            <div class="space-y-1" x-show="['openai'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">AI Model</label>
                                <select wire:model.blur="nodeModel" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                    <option value="gpt-4o">GPT-4o (Smartest)</option>
                                    <option value="gpt-4o-mini">GPT-4o Mini (Fastest)</option>
                                </select>
                            </div>



                            <!-- Method (Webhook) -->
                             <div class="space-y-1" x-show="['webhook'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Method</label>
                                <select wire:model.blur="nodeMethod" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </div>

                            <!-- CRM Sync Properties -->
                            <div class="space-y-1" x-show="['crm_sync'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">CRM Provider</label>
                                <select wire:model.blur="nodeProvider" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                    <option value="salesforce">Salesforce</option>
                                    <option value="hubspot">HubSpot</option>
                                    <option value="zoho">Zoho CRM</option>
                                    <option value="pipedrive">Pipedrive</option>
                                </select>

                                <label class="block text-xs font-bold text-slate-500 uppercase mt-4">Action</label>
                                <select wire:model.blur="nodeAction" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                    <option value="create_lead">Create Lead</option>
                                    <option value="update_lead">Update Lead</option>
                                    <option value="add_timeline">Add Timeline Event</option>
                                </select>
                            </div>

                            <!-- Standalone Delay Node Properties -->
                            <div class="space-y-4" x-show="selectedNode.type === 'delay'">
                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-slate-500 uppercase transition-colors" :class="getFieldError('nodeDelayValue') ? 'text-rose-500' : ''">Wait Duration</label>
                                    <div class="flex gap-2">
                                        <input type="number" wire:model.blur="nodeDelayValue" wire:change="updateNodeData"
                                            class="w-24 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 transition-all shadow-sm"
                                            :class="getFieldError('nodeDelayValue') ? 'border-rose-500 ring-rose-500/20' : ''">
                                        <select wire:model.live="nodeDelayUnit" wire:change="updateNodeData"
                                            class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200 transition-all shadow-sm">
                                            <option value="seconds">Seconds</option>
                                            <option value="minutes">Minutes</option>
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                    <p x-show="getFieldError('nodeDelayValue')" class="text-[10px] text-rose-500 font-bold px-1 mt-1" x-text="getFieldError('nodeDelayValue')?.message"></p>
                                    <p x-show="!getFieldError('nodeDelayValue')" class="text-[10px] text-slate-400">Short delays (< 15 min) are high precision. Longer delays use background scheduler.</p>
                                </div>
                            </div>

                            <!-- Location Request Message -->
                            <div class="space-y-1" x-show="['location_request'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Request Message</label>
                                <textarea wire:model.blur="nodeText" wire:change="updateNodeData" rows="4" @focus="initTribute"
                                    class="mentionable w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200"
                                    placeholder="Please share your location..."></textarea>
                            </div>

                            <!-- Contact Node Properties -->
                            <div class="space-y-1" x-show="['contact'].includes(selectedNode.type)">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold text-slate-500 uppercase">Contacts</label>
                                    <button wire:click="addContact" class="text-[10px] uppercase font-bold text-wa-teal hover:underline">+ Add</button>
                                </div>
                                <div class="space-y-4">
                                    @foreach($nodeContacts as $index => $contact)
                                        <div class="p-3 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl space-y-2 relative group">
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-slate-400 uppercase">Name</label>
                                                <input type="text" wire:model.blur="nodeContacts.{{ $index }}.name" wire:change="updateNodeData"
                                                    class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-slate-400 uppercase">Phone</label>
                                                <input type="text" wire:model.blur="nodeContacts.{{ $index }}.phone" wire:change="updateNodeData"
                                                    class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5">
                                            </div>
                                            <button wire:click="removeContact({{ $index }})" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Webhook Headers -->
                            <div class="space-y-2" x-show="['webhook'].includes(selectedNode.type)">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-500 uppercase">Headers</label>
                                    <button wire:click="addHeader" class="text-[10px] uppercase font-bold text-wa-teal hover:underline">+ Add</button>
                                </div>
                                <div class="space-y-2">
                                    @foreach($nodeHeaders as $index => $header)
                                        <div class="flex gap-2">
                                            <input type="text" wire:model.blur="nodeHeaders.{{ $index }}.key" wire:change="updateNodeData" placeholder="Key"
                                                class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono">
                                            <input type="text" wire:model.blur="nodeHeaders.{{ $index }}.value" wire:change="updateNodeData" placeholder="Value"
                                                class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono">
                                            <button wire:click="removeHeader({{ $index }})" class="text-slate-400 hover:text-red-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                             <!-- Webhook JSON Body -->
                            <div class="space-y-1" x-show="['webhook'].includes(selectedNode.type) && nodeMethod !== 'GET'">
                                <label class="block text-xs font-bold text-slate-500 uppercase">JSON Body</label>
                                <textarea wire:model.blur="nodeJson" wire:change="updateNodeData" rows="6"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200"
                                    placeholder='{"key": "value"}'></textarea>
                            </div>

                            <!-- Send Flow Selection -->
                            <div class="space-y-1" x-show="selectedNode.type === 'send_flow'">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Select Flow to Send</label>
                                <select wire:model.blur="nodeSaveTo" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200">
                                    <option value="">Select a Flow...</option>
                                    @foreach($availableFlows as $flow)
                                        <option value="{{ $flow['id'] }}">{{ $flow['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Save Response To / Variable -->
                             <div class="space-y-1" x-show="['user_input', 'openai', 'condition'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Target Variable</label>
                                 <div class="flex items-center gap-2">
                                    <span class="text-slate-400 font-mono text-sm">@</span>
                                    <input type="text" wire:model.blur="nodeSaveTo" wire:change="updateNodeData"
                                        class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200"
                                        placeholder="variable_name">
                                 </div>
                            </div>
                            
                            <!-- Send Flow Button Text -->

                            <div class="space-y-1" x-show="selectedNode.type === 'send_flow'">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Button Text</label>
                                <input type="text" wire:model.blur="nodeText" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200"
                                    placeholder="e.g. Open Form">
                            </div>

                            <!-- Interactive List Button Text -->
                            <div class="space-y-1" x-show="selectedNode.type === 'interactive_list'">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Button Text</label>
                                <input type="text" wire:model.blur="nodeButtonText" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal text-slate-700 dark:text-slate-200"
                                    placeholder="e.g. View List">
                            </div>

                            <!-- Options / Buttons List -->
                             <div class="space-y-2 pt-4 border-t border-slate-100 dark:border-slate-800" x-show="['interactive_button', 'interactive_list'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase" x-text="selectedNode.type === 'interactive_list' ? 'List Rows' : 'Buttons'"></label>
                                <ul class="space-y-2">
                                    @foreach($nodeOptions as $index => $option)
                                        <li class="flex items-center gap-2 group">
                                            <div class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg text-sm border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                                                {{ is_array($option) ? $option['label'] : $option }}
                                            </div>
                                            <button wire:click="removeOption({{ $index }})" class="text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="flex gap-2">
                                    <input type="text" wire:model="newOption" placeholder="Add button..."
                                        class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal">
                                    <button wire:click="addOption" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-bold">+</button>
                                </div>
                            </div>
                            
                            <!-- Carousel Editor -->
                            <div class="space-y-4" x-show="selectedNode.type === 'carousel'">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Carousel Cards</label>
                                <div class="space-y-6">
                                    @foreach($nodeCards as $cardIndex => $card)
                                        <div class="p-3 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl space-y-3 relative group">
                                            <div class="absolute right-2 top-2">
                                                <button wire:click="removeCard({{ $cardIndex }})" class="text-slate-400 hover:text-red-500 opacity-50 hover:opacity-100">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>

                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-slate-400 uppercase">Image URL</label>
                                                <input type="text" wire:model.blur="nodeCards.{{ $cardIndex }}.image" wire:change="updateCardData"
                                                    class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5"
                                                    placeholder="https://...">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-slate-400 uppercase">Title</label>
                                                <input type="text" wire:model.blur="nodeCards.{{ $cardIndex }}.title" wire:change="updateCardData"
                                                    class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5"
                                                    placeholder="Card Title">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[10px] font-bold text-slate-400 uppercase">Description</label>
                                                <textarea wire:model.blur="nodeCards.{{ $cardIndex }}.description" wire:change="updateCardData" rows="2"
                                                    class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs px-2 py-1.5"
                                                    placeholder="Description..."></textarea>
                                            </div>

                                            <!-- Buttons -->
                                            <div class="pt-2 border-t border-slate-200 dark:border-slate-700">
                                                 <label class="text-[10px] font-bold text-slate-400 uppercase mb-2 block">Buttons (Max 3)</label>
                                                 <div class="space-y-2">
                                                     @foreach($card['buttons'] as $btnIndex => $btn)
                                                        <div class="flex items-center gap-2">
                                                            <input type="text" wire:model.blur="nodeCards.{{ $cardIndex }}.buttons.{{ $btnIndex }}.title" wire:change="updateCardData"
                                                                class="flex-1 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs px-2 py-1.5">
                                                            <button wire:click="removeCardButton({{ $cardIndex }}, {{ $btnIndex }})" class="text-slate-400 hover:text-red-500">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </div>
                                                     @endforeach
                                                     @if(count($card['buttons']) < 3)
                                                        <button wire:click="addCardButton({{ $cardIndex }})" class="text-[10px] bg-slate-200 dark:bg-slate-700 px-2 py-1 rounded hover:bg-slate-300">
                                                            + Add Button
                                                        </button>
                                                     @endif
                                                 </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    <button wire:click="addCard" class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold">
                                        + Add Card
                                    </button>
                                </div>
                            </div>

                            <div class="pt-6 mt-6 border-t border-slate-100 dark:border-slate-800">
                                 <button wire:click="duplicateNode" class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-300 rounded-xl text-sm font-bold flex items-center justify-center gap-2">
                                     Duplicate Node
                                 </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Edge Properties -->
                <div x-show="selectedEdgeIndex !== null">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Condition Label</label>
                    <input type="text" wire:model.blur="edgeCondition" wire:change="updateEdgeData"
                           class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-teal focus:border-wa-teal"
                           placeholder="e.g. Yes, No, > 100">
                </div>

            </div>
        </div>

    </div>

    <!-- Publish Review Modal -->
    <x-dialog-modal wire:model.live="showPublishModal" maxWidth="2xl">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-800 dark:text-white">Publish Flow Review</h2>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">Moving to Version v{{ $version + 1 }}</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6 py-2">
                
                <!-- Summary Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 transform transition-all hover:scale-[1.02]">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Nodes</span>
                        <span class="text-2xl font-black text-slate-700 dark:text-white">{{ count($nodes) }}</span>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 transform transition-all hover:scale-[1.02]">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Trigger</span>
                        <span class="text-sm font-black text-wa-teal">{{ ucfirst(str_replace('_', ' ', $triggerType)) }}</span>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 transform transition-all hover:scale-[1.02]">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Validation</span>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-sm font-black text-emerald-600">Passed</span>
                        </div>
                    </div>
                </div>

                <!-- Risk Warnings -->
                @if(count($this->risks) > 0)
                <div class="space-y-3">
                    <h4 class="text-xs font-black uppercase text-slate-500 tracking-widest px-1">Risk Assessment</h4>
                    <div class="space-y-2">
                        @foreach($this->risks as $risk)
                            <div class="flex items-start gap-4 p-4 rounded-2xl border transition-all {{ $risk['level'] === 'high' ? 'bg-rose-50 border-rose-100 dark:bg-rose-900/10 dark:border-rose-900/30' : 'bg-amber-50 border-amber-100 dark:bg-amber-900/10 dark:border-amber-800/30' }}">
                                <div class="mt-0.5 p-2 rounded-xl {{ $risk['level'] === 'high' ? 'bg-rose-500 text-white' : 'bg-amber-500 text-white' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $risk['icon'] }}" /></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold {{ $risk['level'] === 'high' ? 'text-rose-700 dark:text-rose-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $risk['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Version Note -->
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase text-slate-500 tracking-widest px-1">Publication Notes</label>
                    <textarea wire:model.blur="publishNote" rows="3" 
                        placeholder="What changed in this version? (e.g. Optimized the welcome branch)"
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-wa-teal focus:border-wa-teal transition-all"></textarea>
                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <button @click="showPublishModal = false"
                    class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                    Back to Editor
                </button>
                <div class="flex gap-3">
                    <button wire:click="confirmPublish"
                        class="px-8 py-2.5 bg-wa-teal hover:bg-wa-dark text-white rounded-xl font-black text-sm shadow-xl shadow-wa-teal/20 transition-all flex items-center gap-2">
                        <span>Go Live Now</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                    </button>
                </div>
            </div>
        </x-slot>
    </x-dialog-modal>

    <!-- Save Error Modal -->
    <x-dialog-modal wire:model.live="showErrorModal">
        <x-slot name="title">
            {{ __('Flow Validation Failed') }}
        </x-slot>

        <x-slot name="content">
            <div class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('Please correct the following errors before saving:') }}
                <ul class="list-disc list-inside mt-2 text-red-600 dark:text-red-400 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button wire:click="$set('showErrorModal', false)"
                class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg font-bold text-sm hover:bg-slate-300 dark:hover:bg-slate-600">
                {{ __('Close') }}
            </button>
        </x-slot>
    </x-dialog-modal>

</div></div>
