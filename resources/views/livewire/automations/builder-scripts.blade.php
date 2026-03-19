<!-- Alpine Logic (Extracted) -->
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
                const canvas = this.$refs.canvas;
                this.ctx = canvas.getContext('2d');
                const animate = () => {
                    this.animationOffset = (this.animationOffset - 1) % 40;
                    this.updateCanvas();
                    requestAnimationFrame(animate);
                };
                requestAnimationFrame(animate);
                this.$watch('nodes', () => this.updateCanvas());
                this.$watch('edges', () => this.updateCanvas());
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
            endPan() { this.isPanning = false; },
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
                        this.ctx.strokeStyle = (this.selectedEdgeIndex === index) ? (isLoop ? '#f59e0b' : '#25D366') : (isLoop ? '#fbbf24' : '#94a3b8');
                        this.ctx.setLineDash(isLoop ? [5, 5] : (this.selectedEdgeIndex === index ? [10, 5] : []));
                        if (this.selectedEdgeIndex === index) this.ctx.lineDashOffset = this.animationOffset;
                        this.ctx.lineWidth = (this.selectedEdgeIndex === index) ? 4 : 3;
                        this.ctx.stroke();
                        this.ctx.setLineDash([]);

                        const midX = (startX + endX) / 2;
                        const midY = (startY + endY) / 2;
                        let text = edge.condition || (source.type === 'condition' ? 'Else' : (source.type.includes('interactive') ? 'Selection' : 'Next'));
                        this.ctx.font = 'bold 11px Inter, sans-serif';
                        const width = this.ctx.measureText(text).width + 16;
                        this.ctx.fillStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#ffffff';
                        this.ctx.strokeStyle = (this.selectedEdgeIndex === index) ? '#25D366' : '#cbd5e1';
                        this.ctx.beginPath();
                        this.ctx.roundRect(midX - width / 2, midY - 10, width, 20, 10);
                        this.ctx.fill();
                        this.ctx.stroke();
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
                    }
                }
            },

            startDrag(event, node) {
                if (!node) return;
                this.draggingNodeId = node.id;
                this.selectedId = node.id;
                this.$wire.selectNode(node.id);
                this.selectedEdgeIndex = null;
                this.dragStartMouse = { x: event.clientX, y: event.clientY };
                this.dragStartNode = { x: node.x, y: node.y };

                const moveHandler = (e) => {
                    const dx = (e.clientX - this.dragStartMouse.x) / this.scale;
                    const dy = (e.clientY - this.dragStartMouse.y) / this.scale;
                    const n = this.nodesArray.find(n => n.id === this.draggingNodeId);
                    if (n) { n.x = this.dragStartNode.x + dx; n.y = this.dragStartNode.y + dy; }
                };
                const upHandler = () => {
                    if (this.draggingNodeId) {
                        const n = this.nodesArray.find(n => n.id === this.draggingNodeId);
                        if (n) this.$wire.updateNodePosition(n.id, n.x, n.y);
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
                    this.$wire.addEdge(this.connectSourceId, targetId);
                }
                this.drawing = false;
            }
        }))
    });
</script>
