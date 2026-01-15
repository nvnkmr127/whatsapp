<div class="h-full flex flex-col bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100"
    x-data="flowBuilder">

    <!-- ... (Keep Toolbar same) ... -->

    <!-- Update Component Palette Buttons to remove .then() -->
    <!-- (I will handle this via multi-edit or assume start of file edit covers x-data, checking line 2) -->

    <!-- Alpine Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flowBuilder', () => ({
                nodes: @entangle('nodes'),
                edges: @entangle('edges'),
                selectedId: null,
                selectedEdgeIndex: null,
                animationOffset: 0,

                init() {
                    const canvas = this.$refs.canvas;
                    this.ctx = canvas.getContext('2d');
                    
                    // Animation Loop
                    const animate = () => {
                        this.animationOffset = (this.animationOffset - 1) % 40; // Speed of animation
                        this.updateCanvas();
                        requestAnimationFrame(animate);
                    };
                    requestAnimationFrame(animate);

                    this.$watch('nodes', () => this.updateCanvas());
                    this.$watch('edges', () => this.updateCanvas());
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
                     this.selectedId = null;
                     this.$wire.deleteNode(id); // entangle will update nodes automatically
                },

                updateCanvas() {
                    if (!this.$refs.canvas) return; 
                    const canvas = this.$refs.canvas;
                    if (canvas.width !== 10000) canvas.width = 10000;
                    if (canvas.height !== 10000) canvas.height = 10000;
                    
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Safety check for edges
                    if (!this.edges) return;

                    this.edges.forEach((edge, index) => {
                        const source = this.nodes.find(n => n.id === edge.source);
                        const target = this.nodes.find(n => n.id === edge.target);

                        if (source && target) {
                            const startX = source.x + 288 + 16 + 5000; 
                            const startY = source.y + 48 + 5000;     
                            
                            
                            const endX = target.x - 16 + 5000;       
                            const endY = target.y + 48 + 5000;

                            this.ctx.beginPath();
                            this.ctx.moveTo(startX, startY);
                            const cpDist = Math.abs(endX - startX) * 0.5 + 50; 
                            this.ctx.bezierCurveTo(startX + cpDist, startY, endX - cpDist, endY, endX, endY);
                            
                            this.ctx.strokeStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#94a3b8';
                            this.ctx.lineWidth = 3;
                            
                            // ANIMATION
                            this.ctx.setLineDash([8, 6]);
                            this.ctx.lineDashOffset = this.animationOffset;
                            
                            this.ctx.stroke();
                            
                            // Reset dash for text background
                            this.ctx.setLineDash([]);

                            if (edge.condition || this.selectedEdgeIndex === index) {
                                const midX = (startX + endX) / 2;
                                const midY = (startY + endY) / 2;
                                const text = edge.condition || 'Next';
                                this.ctx.font = 'bold 12px Inter, sans-serif';
                                const width = this.ctx.measureText(text).width + 16;
                                this.ctx.fillStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#f1f5f9';
                                this.ctx.beginPath();
                                this.ctx.roundRect(midX - width/2, midY - 12, width, 24, 12);
                                this.ctx.fill();
                                this.ctx.fillStyle = (this.selectedEdgeIndex === index) ? '#ffffff' : '#64748b';
                                this.ctx.textAlign = 'center';
                                this.ctx.textBaseline = 'middle';
                                this.ctx.fillText(text, midX, midY);
                            }
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
                         const node = this.nodes.find(n => n.id === this.draggingNodeId);
                         if (node) {
                             node.x = this.dragStartNode.x + dx;
                             node.y = this.dragStartNode.y + dy;
                         }
                    };
                    const upHandler = () => {
                        if (this.draggingNodeId) {
                            const node = this.nodes.find(n => n.id === this.draggingNodeId);
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
    <div class="h-16 flex-none bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 z-50">
        <div class="flex items-center gap-4">
            <a href="{{ route('automations.index') }}"
                class="p-2 -ml-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                title="Back to Automations">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700"></div>
            <div class="flex flex-col">
                <h1 class="text-sm font-bold text-slate-800 dark:text-white leading-tight">
                    {{ $name ?? 'Untitled Automation' }}
                </h1>
                <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">
                    {{ $triggerType === 'keyword' ? 'Keywords: ' . implode(', ', $triggerConfig['keywords'] ?? []) : ucfirst(str_replace('_', ' ', $triggerType)) }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
             <div class="px-3 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-mono border border-indigo-100 dark:border-indigo-800 flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                Live Editor
            </div>
            <button wire:click="save"
                class="bg-wa-green hover:bg-wa-dark text-white text-sm font-bold px-5 py-2 rounded-xl flex items-center gap-2 transition shadow-lg shadow-wa-green/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Save Flow
            </button>
        </div>
    </div>

    <!-- Workspace -->
    <div class="flex-1 flex overflow-hidden relative">



        <!-- Left Sidebar: Component Palette -->
        <div class="w-72 flex-none bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col z-40 transition-all duration-300"
             :class="{'w-72': true, 'w-0 opacity-0 overflow-hidden': false}">
            
            <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" placeholder="Search components..."
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs py-2.5 pl-9 pr-3 focus:ring-2 focus:ring-wa-green focus:border-transparent transition-shadow">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-6 scrollbar-hide">
                 <!-- Component Groups -->
                 @foreach([
                    'Messages' => [
                        ['type' => 'text', 'label' => 'Text Message', 'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                        ['type' => 'image', 'label' => 'Image / Media', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
                        ['type' => 'template', 'label' => 'WhatsApp Template', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'text-green-500', 'bg' => 'bg-green-50 dark:bg-green-900/20'],
                        ['type' => 'interactive_button', 'label' => 'Reply Buttons', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                        ['type' => 'interactive_list', 'label' => 'List Menu', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
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
                                <button @click="$wire.addNode('{{ $item['type'] }}').then(() => { nodes = $wire.nodes; edges = $wire.edges; })"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 bg-white dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/50 hover:border-wa-green/30 dark:hover:border-wa-green/30 rounded-xl transition-all group shadow-sm hover:shadow-md hover:scale-[1.02]">
                                    <div class="p-2 rounded-lg {{ $item['bg'] }} {{ $item['color'] }} transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col items-start">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-wa-green transition-colors">{{ $item['label'] }}</span>
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
                    <div class="absolute w-72 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 cursor-default transition-shadow hover:shadow-2xl group flex flex-col z-10"
                        :class="{'ring-2 ring-wa-green dark:ring-wa-green ring-offset-2 ring-offset-slate-50 dark:ring-offset-slate-950': selectedId === node.id}"
                        :style="`left: ${node.x}px; top: ${node.y}px`"
                        @mousedown.stop="startDrag($event, node)"
                        @click.stop="$wire.selectNode(node.id)">
                        
                        <!-- ... Node Content ... -->
                        <!-- ... (Keep existing node content logic implicitly as we operate on the wrapping div) ... -->
                        <!-- I need to be careful not to replace the inner content if I just target the canvas tag area. -->
                        <!-- Actually, the Replace tool works on chunks. I should target the Canvas tag specifically and endConnect specifically. -->

                        <!-- Wait, ReplaceFileContent works on lines. I can just replace the Canvas Line and the Script block. -->
                        <!-- But they are far apart. I'll do 2 edits. -->


                        <!-- Node Header -->
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50 rounded-t-2xl">
                             <div class="flex items-center gap-3">
                                 <span class="w-2 h-2 rounded-full" 
                                       :class="{
                                           'bg-blue-500': ['text','message'].includes(node.type),
                                           'bg-green-500': node.type === 'template',
                                           'bg-purple-500': ['image','media'].includes(node.type),
                                           'bg-orange-500': ['interactive_button','interactive_list'].includes(node.type),
                                           'bg-amber-500': ['condition','trigger'].includes(node.type),
                                           'bg-cyan-500': node.type === 'user_input',
                                           'bg-pink-500': ['webhook'].includes(node.type),
                                           'bg-indigo-500': ['crm_sync'].includes(node.type),
                                       }"></span>
                                 <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide" x-text="node.data.label || node.type"></span>
                             </div>
                             <button @click.stop="deleteNode(node.id)" class="text-slate-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                             </button>
                        </div>

                        <!-- Node Body -->
                        <div class="p-4 space-y-2">
                             <!-- Preview Text -->
                             <div class="text-xs text-slate-500 dark:text-slate-400 line-clamp-3 min-h-[1.5em]"
                                  x-text="node.data.text || node.data.question || node.data.caption || node.data.template_name || 'Generic Node'">
                             </div>
                             
                             <!-- Tags (Buttons/Options) -->
                             <div class="flex flex-wrap gap-1">
                                 <template x-for="option in (node.data.options || (node.data.buttons ? node.data.buttons.map(b=>b.title) : []))">
                                     <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700" x-text="option"></span>
                                 </template>
                             </div>
                             
                             <!-- Type Specific Badges -->
                             <div x-show="node.type === 'openai'" class="flex items-center gap-1 text-[10px] text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span>GPT-4o</span>
                             </div>
                        </div>

                        <!-- Handles (Hit Areas Enhanced) -->
                        <!-- Input -->
                        <div class="absolute -left-4 top-8 w-8 h-8 flex items-center justify-center z-20 cursor-crosshair group/handle"
                             @mouseup="endConnect(node.id)">
                             <div class="w-4 h-4 bg-slate-100 dark:bg-slate-800 rounded-full border-2 border-slate-300 dark:border-slate-600 group-hover/handle:border-wa-green group-hover/handle:scale-125 transition-all"></div>
                        </div>

                        <!-- Output -->
                        <div class="absolute -right-4 top-8 w-8 h-8 flex items-center justify-center z-20 cursor-crosshair group/handle"
                             @mousedown.stop="startConnect($event, node.id)">
                             <div class="w-4 h-4 bg-wa-green rounded-full border-2 border-white dark:border-slate-900 group-hover/handle:scale-125 transition-all shadow-sm"></div>
                        </div>

                    </div>
                </template>

            </div>
        </div>

        <!-- Right Sidebar: Properties -->
        <div class="w-80 flex-none bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 flex flex-col z-40 shadow-xl"
             x-show="selectedId || selectedEdgeIndex !== null"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">
            
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="font-bold text-sm uppercase tracking-wider text-slate-900 dark:text-white">Properties</h3>
                <button @click="deselectAll()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-6">
                
                <!-- Node Properties -->
                <div x-show="selectedId">
                    <template x-if="selectedNode">
                        <div class="space-y-6">
                            
                            <!-- Debug Info (Optional, remove later) -->
                            <!-- <div class="text-[10px] text-slate-400">Type: <span x-text="selectedNode.type"></span></div> -->

                            <!-- Trigger Configuration -->
                            <div x-show="selectedNode.type === 'trigger'">
                                <div class="space-y-4">
                                     <!-- Trigger Type Dropdown -->
                                    <div class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Trigger Event</label>
                                        <select wire:model.live="triggerType" wire:change="updateNodeData"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200">
                                            <option value="keyword">Keyword/Regex Match</option>
                                            <option value="user_starts_conversation">User Starts Conversation</option>
                                            <option value="template_selected">Template Selected</option>
                                            <option value="template_delivered">WhatsApp Template Delivered</option>
                                            <option value="contact_added">Contact Added</option>
                                            <option value="added_to_list">Contact Added to Contact List</option>
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
                                                    <input type="text" wire:model.blur="triggerConfig.keywords.{{ $index }}" 
                                                        class="flex-1 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold px-2 py-1.5"
                                                        placeholder="Keyword">
                                                    <button wire:click="removeTriggerKeyword({{ $index }})" class="text-slate-400 hover:text-rose-500">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                             <button wire:click="addTriggerKeyword"
                                                class="text-xs font-bold text-wa-green hover:underline">+ Add Keyword</button>
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

                                     <!-- Template Selected -->
                                    <div x-show="['template_selected'].includes($wire.triggerType)" class="space-y-3">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Template</label>
                                        <!-- Ideally a Select from DB, but text for now -->
                                        <input type="text" wire:model.blur="triggerConfig.template_id" placeholder="Template ID / Name"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                                        
                                        <label class="block text-xs font-bold text-slate-500 uppercase mt-2">Button Payload</label>
                                        <input type="text" wire:model.blur="triggerConfig.button_payload" placeholder="Button ID"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                                    </div>

                                    <!-- Tag Assigned -->
                                    <div x-show="['tag_assigned'].includes($wire.triggerType)" class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">Tag Name</label>
                                        <input type="text" wire:model.blur="triggerConfig.tag_name" placeholder="e.g. VVIP"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                                    </div>

                                    <!-- Contact List -->
                                    <div x-show="['added_to_list'].includes($wire.triggerType)" class="space-y-1">
                                        <label class="block text-xs font-bold text-slate-500 uppercase">List ID</label>
                                        <input type="text" wire:model.blur="triggerConfig.list_id" placeholder="List ID"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm">
                                    </div>
                                    
                                    <!-- START NODE ACTIONS -->
                                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                                        <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Start Actions</h4>
                                        
                                        <!-- Add Tags -->
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase">Add Tags</label>
                                                <button wire:click="addStartTag" class="text-[10px] uppercase font-bold text-wa-green hover:underline">+ Add</button>
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
                                                <button wire:click="addRemoveTag" class="text-[10px] uppercase font-bold text-wa-green hover:underline">+ Add</button>
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

                            <!-- Text / Content -->
                            <div x-show="['text', 'interactive_button', 'interactive_list', 'user_input', 'openai', 'template', 'delay'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2" x-text="
                                    selectedNode.type === 'user_input' ? 'Question' : 
                                    (selectedNode.type === 'openai' ? 'Prompt' : 
                                    (selectedNode.type === 'delay' ? 'Seconds' : 'Please provide your reply message'))
                                "></label>
                                
                                <div class="relative">
                                    <textarea wire:model.blur="nodeText" wire:change="updateNodeData" rows="6"
                                        class="w-full bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200 p-4 leading-relaxed"
                                        :placeholder="selectedNode.type === 'openai' ? 'Enter system instructions...' : 'WhatsApp text message limit is 4096 characters.'"></textarea>
                                     <div class="absolute top-3 right-3 text-slate-400">
                                         <svg class="w-5 h-5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                     </div>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1" x-show="selectedNode.type !== 'text'">Markdown enabled. Use @{{variable}} for dynamic data.</p>
                            </div>
                            
                            <!-- Typing and Delay (Text Node Only) -->
                             <div x-show="selectedNode.type === 'text'" class="space-y-6 pt-4 border-t border-slate-100 dark:border-slate-800">
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
                                         <input type="range" min="0" max="60" wire:model.live="nodeDelaySeconds" wire:change="updateNodeData" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-wa-green">
                                     </div>

                                      <!-- Minutes -->
                                     <div class="space-y-2 mb-4">
                                         <div class="flex justify-between text-[11px] font-bold text-slate-600 dark:text-slate-400">
                                             <span>minutes: {{ $nodeDelayMinutes }}</span>
                                         </div>
                                         <input type="range" min="0" max="60" wire:model.live="nodeDelayMinutes" wire:change="updateNodeData" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-wa-green">
                                     </div>

                                      <!-- Hours -->
                                     <div class="space-y-2">
                                         <div class="flex justify-between text-[11px] font-bold text-slate-600 dark:text-slate-400">
                                             <span>hours: {{ $nodeDelayHours }}</span>
                                         </div>
                                         <input type="range" min="0" max="24" wire:model.live="nodeDelayHours" wire:change="updateNodeData" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-wa-green">
                                     </div>
                                 </div>
                             </div>

                            <!-- OpenAI Model -->
                            <div class="space-y-1" x-show="['openai'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">AI Model</label>
                                <select wire:model.blur="nodeModel" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200">
                                    <option value="gpt-4o">GPT-4o (Smartest)</option>
                                    <option value="gpt-4o-mini">GPT-4o Mini (Fastest)</option>
                                </select>
                            </div>

                            <!-- URL / Resource -->
                             <div class="space-y-1" x-show="['image', 'video', 'audio', 'file', 'webhook'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Resource / URL</label>
                                <input type="text" wire:model.blur="nodeUrl" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200"
                                    placeholder="https://... or ID">
                            </div>

                            <!-- Method (Webhook) -->
                             <div class="space-y-1" x-show="['webhook'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Method</label>
                                <select wire:model.blur="nodeMethod" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                </select>
                            </div>

                            <!-- Webhook Headers -->
                            <div class="space-y-2" x-show="['webhook'].includes(selectedNode.type)">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-500 uppercase">Headers</label>
                                    <button wire:click="addHeader" class="text-[10px] uppercase font-bold text-wa-green hover:underline">+ Add</button>
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
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200"
                                    placeholder='{"key": "value"}'></textarea>
                            </div>

                            <!-- Save Response To -->
                             <div class="space-y-1" x-show="['user_input', 'openai', 'send_flow'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase" x-text="selectedNode.type === 'send_flow' ? 'Flow Name / ID' : 'Save Variable'"></label>
                                 <div class="flex items-center gap-2">
                                    <span class="text-slate-400 font-mono text-sm" x-show="selectedNode.type !== 'send_flow'">@</span>
                                    <input type="text" wire:model.blur="nodeSaveTo" wire:change="updateNodeData"
                                        class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200"
                                        :placeholder="selectedNode.type === 'send_flow' ? 'Flow ID (e.g. 123456...)' : 'variable_name'">
                                 </div>
                            </div>
                            
                            <!-- Send Flow Button Text -->
                            <div class="space-y-1" x-show="selectedNode.type === 'send_flow'">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Button Text</label>
                                <input type="text" wire:model.blur="nodeText" wire:change="updateNodeData"
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green text-slate-700 dark:text-slate-200"
                                    placeholder="e.g. Open Form">
                            </div>

                            <!-- Options / Buttons List -->
                             <div class="space-y-2 pt-4 border-t border-slate-100 dark:border-slate-800" x-show="['interactive_button'].includes(selectedNode.type)">
                                <label class="block text-xs font-bold text-slate-500 uppercase">Buttons</label>
                                <ul class="space-y-2">
                                    @foreach($nodeOptions as $index => $option)
                                        <li class="flex items-center gap-2 group">
                                            <div class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-lg text-sm border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                                                {{ $option }}
                                            </div>
                                            <button wire:click="removeOption({{ $index }})" class="text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="flex gap-2">
                                    <input type="text" wire:model="newOption" placeholder="Add button..."
                                        class="flex-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green">
                                    <button wire:click="addOption" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-bold">+</button>
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
                           class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-wa-green focus:border-wa-green"
                           placeholder="e.g. Yes, No, > 100">
                </div>

            </div>
        </div>

    </div>

    <!-- Alpine Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('flowBuilder', (initialNodes, initialEdges) => ({
                nodes: initialNodes,
                edges: initialEdges,
                selectedId: null,
                selectedEdgeIndex: null,
                
                panX: 0, panY: 0, scale: 1, isPanning: false, panStart: { x: 0, y: 0 },
                draggingNodeId: null, drawing: false, connectSourceId: null, mouse: { x: 0, y: 0 }, ctx: null,

                init() {
                    const canvas = this.$refs.canvas;
                    this.ctx = canvas.getContext('2d');
                    this.$watch('nodes', () => this.drawEdges());
                    this.$watch('edges', () => this.drawEdges());
                    const animate = () => { this.drawEdges(); requestAnimationFrame(animate); };
                    requestAnimationFrame(animate);
                },

                refreshAlpine() {
                    // Clone to detach from proxy and ensure clean state
                    this.nodes = JSON.parse(JSON.stringify(this.$wire.nodes));
                    this.edges = this.$wire.edges;
                },
                
                get selectedNode() {
                    if (!this.selectedId) return null;
                    return this.nodes.find(n => n.id === this.selectedId);
                },
                
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
                     this.selectedId = null;
                     // Optimistic UI update: Remove locally first
                     this.nodes = this.nodes.filter(n => n.id !== id);
                     // Remove associated edges locally too
                     this.edges = this.edges ? this.edges.filter(e => e.source !== id && e.target !== id) : [];
                     
                     // Sync with backend
                     this.$wire.deleteNode(id);
                },

                drawEdges() {
                    if (!this.$refs.canvas) return; 
                    const canvas = this.$refs.canvas;
                    // Ensure canvas size matches the virtual space
                    if (canvas.width !== 10000) canvas.width = 10000;
                    if (canvas.height !== 10000) canvas.height = 10000;
                    
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Safety check for edges
                    if (!this.edges) return;
                    
                    // Ensure edges is array-like for iteration
                    const edgesList = Array.isArray(this.edges) ? this.edges : Object.values(this.edges);

                    // Draw Existing Edges
                    edgesList.forEach((edge, index) => {
                        const source = this.nodes.find(n => n.id === edge.source);
                        const target = this.nodes.find(n => n.id === edge.target);

                        if (source && target) {
                            const startX = source.x + 288 + 16 + 5000; // x + Width + HandleOffset + CanvasOffset
                            const startY = source.y + 48 + 5000;     // y + TopOffset + HalfHeight + CanvasOffset
                            
                            const endX = target.x - 16 + 5000;       // x - HandleOffset + CanvasOffset
                            const endY = target.y + 48 + 5000;

                            this.ctx.beginPath();
                            this.ctx.moveTo(startX, startY);
                            const cpDist = Math.abs(endX - startX) * 0.5 + 50; // curve
                            this.ctx.bezierCurveTo(startX + cpDist, startY, endX - cpDist, endY, endX, endY);
                            
                            this.ctx.strokeStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#94a3b8';
                            this.ctx.lineWidth = 3;
                            this.ctx.stroke();

                            // Label
                            if (edge.condition || this.selectedEdgeIndex === index) {
                                const midX = (startX + endX) / 2;
                                const midY = (startY + endY) / 2;
                                const text = edge.condition || 'Next';
                                this.ctx.font = 'bold 12px Inter, sans-serif';
                                const width = this.ctx.measureText(text).width + 16;
                                this.ctx.fillStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#f1f5f9';
                                this.ctx.beginPath();
                                this.ctx.roundRect(midX - width/2, midY - 12, width, 24, 12);
                                this.ctx.fill();
                                this.ctx.fillStyle = (this.selectedEdgeIndex === index) ? '#ffffff' : '#64748b';
                                this.ctx.textAlign = 'center';
                                this.ctx.textBaseline = 'middle';
                                this.ctx.fillText(text, midX, midY);
                            }
                        }
                    });

                    // Draw Active Connection Line
                    if (this.drawing && this.connectSourceId) {
                        const source = this.nodes.find(n => n.id === this.connectSourceId);
                        if (source) {
                            const startX = source.x + 288 + 16 + 5000;
                            const startY = source.y + 48 + 5000;
                            
                            // Mouse Position relative to Canvas Element
                            const mouseX_in_Canvas = (this.mouse.x - this.panX) / this.scale;
                            const mouseY_in_Canvas = (this.mouse.y - this.panY) / this.scale;
                            
                            const targetX = mouseX_in_Canvas + 5000;
                            const targetY = mouseY_in_Canvas + 5000;
                            
                            this.ctx.beginPath();
                            this.ctx.moveTo(startX, startY);
                            // Draw a curve for the active line too, looks better
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
                         const node = this.nodes.find(n => n.id === this.draggingNodeId);
                         if (node) {
                             node.x = this.dragStartNode.x + dx;
                             node.y = this.dragStartNode.y + dy;
                         }
                    };
                    const upHandler = () => {
                        if (this.draggingNodeId) {
                            const node = this.nodes.find(n => n.id === this.draggingNodeId);
                            if (node) {
                                this.$wire.updateNodePosition(node.id, node.x, node.y);
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
                    
                    // Capture initial pos
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
                        // Optimistic Update: Add locally immediately
                        // Check for duplicates locally first
                        const exists = this.edges && this.edges.some(e => e.source === this.connectSourceId && e.target === targetId);
                        if (!exists) {
                            if (!this.edges) this.edges = [];
                            this.edges.push({ source: this.connectSourceId, target: targetId, condition: '' });
                            // Entangle will sync this change to backend automatically
                        }
                    }
                    this.drawing = false;
                }
            }))
        });
    </script>
</div>