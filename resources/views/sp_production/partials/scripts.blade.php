        <script>
            function productionSession() {
                return {
                    tab: 'all',
                    totals: @json($sessionTotals ?? []),
                    get reworkPending() {
                        const inQty = parseInt(this.totals.rework_in) || 0;
                        const recQty = parseInt(this.totals.rework_recovered) || 0;
                        const scrapQty = parseInt(this.totals.rework_scrapped) || 0;
                        return Math.max(0, inQty - (recQty + scrapQty));
                    },
                    get availableDefectsForRework() {
                        const rawRej = parseInt(this.totals.raw_reject !== undefined ? this.totals.raw_reject : this.totals.reject) || 0;
                        const inQty = parseInt(this.totals.internal_rework_in !== undefined ? this.totals.internal_rework_in : this.totals.rework_in) || 0;
                        return Math.max(0, rawRej - inQty);
                    },
                    targetQty: {{ $session->workOrder->target_qty ?? 1 }},
                    progressPct: 0,
                    liveTime: '',
                    elapsedTime: '',
                    batchSize: 1,
                    customBatchSize: null,
                    get isCustomBatch() {
                        return ![1, 5, 10, 50, 100].includes(this.batchSize);
                    },
                    quickFlash: false,
                    balanceFlash: false,
                    highlightedEntryId: null,
                    productionEntries: @json($session->productionEntries),
                    rejectEntries: @json($session->rejectEntries),
                    downtimeEntries: @json($session->downtimeEntries),
                    reworkEntries: @json($session->reworkEntries),
                    inputEntries: @json($session->inputEntries),
                    manpowerEntries: @json($session->manpowerEntries),
                    roleLabels: @json(config('mes.sp_manpower_roles', [])),

                    pausedAt: @json($session->paused_at?->toIso8601String()),
                    pausedDurationMinutes: 0,

                    get isPaused() {
                        return !!this.pausedAt;
                    },

                    get availableWip() {
                        return Math.max(0, this.totals.input - (this.totals.good + this.totals.reject));
                    },

                    get allRecords() {
                        const list = [];
                        (this.productionEntries || []).forEach(e => list.push({ ...e, streamType: 'production', timestamp: new Date(e.recorded_at || e.created_at).getTime() }));
                        (this.rejectEntries || []).forEach(e => list.push({ ...e, streamType: 'reject', timestamp: new Date(e.created_at).getTime() }));
                        (this.downtimeEntries || []).forEach(e => list.push({ ...e, streamType: 'downtime', timestamp: new Date(e.created_at).getTime() }));
                        (this.reworkEntries || []).forEach(e => list.push({ ...e, streamType: 'rework', timestamp: new Date(e.created_at).getTime() }));
                        (this.inputEntries || []).forEach(e => list.push({ ...e, streamType: 'input', timestamp: new Date(e.created_at).getTime() }));
                        (this.manpowerEntries || []).forEach(e => list.push({ ...e, streamType: 'manpower', timestamp: new Date(e.created_at).getTime() }));
                        return list.sort((a, b) => b.timestamp - a.timestamp);
                    },

                    get latestActivity() {
                        const recs = this.allRecords;
                        return (recs && recs.length > 0) ? recs[0] : null;
                    },

                    get latestActivityText() {
                        const item = this.latestActivity;
                        if (!item) return 'No activity recorded yet for this session';
                        const time = new Date(item.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        if (item.streamType === 'production') {
                            return '+' + this.formatNum(item.good_qty) + ' Good Output logged at ' + time;
                        } else if (item.streamType === 'reject') {
                            return '-' + this.formatNum(item.quantity) + ' ' + (item.defect_type || 'Defect') + ' logged at ' + time;
                        } else if (item.streamType === 'input') {
                            return '+' + this.formatNum(item.quantity) + ' ' + (item.source || 'WIP').toUpperCase() + ' Received at ' + time;
                        } else if (item.streamType === 'rework') {
                            if (item.action === 'issued') return 'Issued ' + this.formatNum(item.quantity) + ' Pcs to Rework at ' + time;
                            return 'Completed Rework (' + this.formatNum(item.recovered_qty) + ' Rec. • ' + this.formatNum(item.scrapped_qty) + ' Scrap) at ' + time;
                        } else if (item.streamType === 'downtime') {
                            return 'Downtime: ' + (item.reason || 'Stoppage') + ' (' + (item.duration_minutes || 0) + 'm) at ' + time;
                        } else if (item.streamType === 'manpower') {
                            return (item.operator_name || 'Operator') + ' joined line team at ' + time;
                        }
                        return 'Activity updated at ' + time;
                    },

                    triggerBalanceFlash() {
                        this.balanceFlash = true;
                        setTimeout(() => this.balanceFlash = false, 600);
                    },

                    init() {
                        // Load persistent custom batch size
                        const savedCustom = localStorage.getItem('sp_custom_batch_size');
                        if (savedCustom && !isNaN(savedCustom) && parseInt(savedCustom) > 0) {
                            this.customBatchSize = parseInt(savedCustom);
                        }

                        // Load persistent quick-tap batch size
                        const savedBatch = localStorage.getItem('sp_quick_batch_size');
                        if (savedBatch && !isNaN(savedBatch) && parseInt(savedBatch) > 0) {
                            this.batchSize = parseInt(savedBatch);
                        }

                        this.updateClock();
                        setInterval(() => this.updateClock(), 1000);
                        this.recalcProgress();

                        this.$watch('manpowerEntries', (entries) => {
                            window.dispatchEvent(new CustomEvent('team-count-changed', { detail: { count: entries ? entries.length : 0 } }));
                        });
                    },

                    setBatchSize(size) {
                        this.batchSize = parseInt(size);
                        localStorage.setItem('sp_quick_batch_size', this.batchSize);
                    },

                    applyCustomBatchSize(qty) {
                        const val = parseInt(qty);
                        if (!val || val <= 0) return;
                        this.customBatchSize = val;
                        this.batchSize = val;
                        localStorage.setItem('sp_custom_batch_size', val);
                        localStorage.setItem('sp_quick_batch_size', val);
                    },

                    openInputModal(type = 'wip') {
                        const modal = document.getElementById('modalInput');
                        if (modal) {
                            modal.showModal();
                            const target = modal.querySelector('[x-data]') || modal.querySelector('form');
                            if (target && target._x_dataStack && target._x_dataStack[0]) {
                                target._x_dataStack[0].source = type;
                            }
                        }
                    },

                    updateClock() {
                        const now = new Date();
                        this.liveTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });

                        if (this.pausedAt) {
                            const paused = new Date(this.pausedAt);
                            const diffSec = Math.max(0, Math.floor((now - paused) / 1000));
                            const hrs = Math.floor(diffSec / 3600);
                            const mins = Math.floor((diffSec % 3600) / 60);
                            this.pausedDurationMinutes = Math.max(1, Math.round(diffSec / 60));
                            this.elapsedTime = (hrs > 0 ? hrs + 'h ' : '') + mins + 'm (PAUSED)';
                        } else {
                            @if($session->started_at)
                                const started = new Date('{{ $session->started_at->toIso8601String() }}');
                                const diffSec = Math.max(0, Math.floor((now - started) / 1000));
                                const hrs = Math.floor(diffSec / 3600);
                                const mins = Math.floor((diffSec % 3600) / 60);
                                this.elapsedTime = hrs + 'h ' + mins + 'm';
                            @else
                                this.elapsedTime = '0h 0m';
                            @endif
                        }
                    },

                    async togglePause() {
                        if (this.isPaused) {
                            document.getElementById('modalResumeDowntime').showModal();
                            return;
                        }

                        if (!confirm('Pause production line for breakdown / downtime?')) return;

                        try {
                            const response = await fetch('{{ route("app.sp-sessions.pause", $session->id) }}', {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });

                            if (!response.ok) {
                                const err = await response.json().catch(() => ({}));
                                throw new Error(err.error || 'Failed to pause line.');
                            }

                            const data = await response.json();
                            if (data.success) {
                                this.pausedAt = data.paused_at;
                                this.updateClock();
                                window.dispatchEvent(new CustomEvent('line-pause-changed', { detail: { isPaused: true } }));
                            }
                        } catch (error) {
                            alert(error.message);
                        }
                    },

                    async submitResumeForm(event) {
                        const form = event.target;
                        const formData = new FormData(form);

                        try {
                            const response = await fetch('{{ route("app.sp-sessions.resume", $session->id) }}', {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            if (!response.ok) {
                                const err = await response.json().catch(() => ({}));
                                throw new Error(err.error || 'Failed to resume line.');
                            }

                            const data = await response.json();
                            if (data.success) {
                                this.pausedAt = null;
                                window.dispatchEvent(new CustomEvent('line-pause-changed', { detail: { isPaused: false } }));
                                if (data.downtime) {
                                    this.downtimeEntries.unshift(data.downtime);
                                    this.triggerHighlight(data.downtime.id);
                                }
                                if (data.totals) {
                                    this.totals = { ...this.totals, ...data.totals };
                                }
                                form.closest('dialog').close();
                                form.reset();
                            }
                        } catch (error) {
                            alert(error.message);
                        }
                    },

                    recalcProgress() {
                        if (this.targetQty > 0) {
                            this.progressPct = Math.min(100, Math.round((this.totals.good / this.targetQty) * 100));
                        } else {
                            this.progressPct = 0;
                        }
                    },

                    triggerHighlight(entryId) {
                        if (entryId) {
                            this.highlightedEntryId = entryId;
                            setTimeout(() => {
                                if (this.highlightedEntryId === entryId) {
                                    this.highlightedEntryId = null;
                                }
                            }, 1500);
                        }
                    },

                    async removeManpower(manpowerId) {
                        if (!confirm('Remove worker from line team?')) return;

                        try {
                            const response = await fetch(`{{ url('app/sp-sessions/' . $session->id . '/manpower') }}/${manpowerId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });

                            if (!response.ok) {
                                const err = await response.json().catch(() => ({}));
                                throw new Error(err.error || 'Failed to remove team member.');
                            }

                            const data = await response.json();
                            if (data.success) {
                                this.manpowerEntries = this.manpowerEntries.filter(m => m.id !== manpowerId);
                                window.dispatchEvent(new CustomEvent('team-count-changed', { detail: { count: this.manpowerEntries.length } }));
                            }
                        } catch (error) {
                            alert(error.message);
                        }
                    },

                    async deleteEntry(item, explicitType = null) {
                        if (!confirm('Remove this log entry? Session totals will recalculate automatically.')) return;

                        const type = explicitType || item.streamType || (
                            this.tab === 'defects' ? 'reject' :
                            (this.tab !== 'all' ? this.tab : null)
                        ) || (
                            item.good_qty !== undefined ? 'production' :
                            item.defect_type !== undefined ? 'reject' :
                            item.duration_minutes !== undefined ? 'downtime' :
                            (item.recovered_qty !== undefined || item.scrapped_qty !== undefined) ? 'rework' :
                            (item.source !== undefined || (item.quantity !== undefined && item.defect_type === undefined)) ? 'input' :
                            item.role !== undefined ? 'manpower' : null
                        );

                        let endpoint = '';
                        if (type === 'production') endpoint = `{{ url('app/sp-sessions/' . $session->id . '/production') }}/${item.id}`;
                        else if (type === 'reject') endpoint = `{{ url('app/sp-sessions/' . $session->id . '/reject') }}/${item.id}`;
                        else if (type === 'downtime') endpoint = `{{ url('app/sp-sessions/' . $session->id . '/downtime') }}/${item.id}`;
                        else if (type === 'rework') endpoint = `{{ url('app/sp-sessions/' . $session->id . '/rework') }}/${item.id}`;
                        else if (type === 'input') endpoint = `{{ url('app/sp-sessions/' . $session->id . '/input') }}/${item.id}`;
                        else if (type === 'manpower') {
                            this.removeManpower(item.id);
                            return;
                        }

                        if (!endpoint) return;

                        try {
                            const response = await fetch(endpoint, {
                                method: 'DELETE',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });

                            if (!response.ok) {
                                const err = await response.json().catch(() => ({}));
                                throw new Error(err.error || 'Failed to remove entry.');
                            }

                            const data = await response.json();
                            if (data.success) {
                                if (type === 'production') this.productionEntries = this.productionEntries.filter(e => e.id !== item.id);
                                else if (type === 'reject') this.rejectEntries = this.rejectEntries.filter(e => e.id !== item.id);
                                else if (type === 'downtime') this.downtimeEntries = this.downtimeEntries.filter(e => e.id !== item.id);
                                else if (type === 'rework') {
                                    this.reworkEntries = this.reworkEntries.filter(e => e.id !== item.id);
                                    if (data.deleted_input_id) {
                                        this.inputEntries = this.inputEntries.filter(e => e.id !== data.deleted_input_id);
                                    }
                                }
                                else if (type === 'input') {
                                    this.inputEntries = this.inputEntries.filter(e => e.id !== item.id);
                                    if (data.deleted_rework_id) {
                                        this.reworkEntries = this.reworkEntries.filter(e => e.id !== data.deleted_rework_id);
                                    }
                                }

                                if (data.totals) {
                                    this.totals = { ...this.totals, ...data.totals };
                                    this.recalcProgress();
                                }
                            }
                        } catch (error) {
                            alert(error.message);
                        }
                    },

                    async logOneTimeGood(qty) {
                        const val = parseInt(qty);
                        if (!val || val <= 0) return;
                        if (this.isPaused) {
                            document.getElementById('modalResumeDowntime').showModal();
                            return;
                        }

                        if (val > this.availableWip) {
                            alert('Available Input (' + this.availableWip + ' Pcs) is less than requested quantity (+' + val + ' Pcs). Please receive stock first.');
                            this.openInputModal('wip');
                            return;
                        }

                        try {
                            const formData = new FormData();
                            formData.append('good_qty', val);
                            formData.append('reject_qty', 0);
                            formData.append('_token', '{{ csrf_token() }}');

                            const response = await fetch('{{ route("app.sp-sessions.add-production", $session->id) }}', {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            if (!response.ok) {
                                const err = await response.json().catch(() => ({}));
                                throw new Error(err.error || 'Quick log failed');
                            }

                            const data = await response.json();
                            if (data.success) {
                                this.totals = { ...this.totals, ...data.totals };
                                this.triggerBalanceFlash();
                                this.productionEntries.unshift(data.entry);
                                this.tab = 'production';
                                this.triggerHighlight(data.entry?.id);
                                this.recalcProgress();
                                this.quickFlash = true;
                                setTimeout(() => this.quickFlash = false, 600);
                            }
                        } catch (error) {
                            alert(error.message);
                        }
                    },

                    quickAddGood() {
                        return this.logOneTimeGood(this.batchSize);
                    },

                    async submitForm(event, type) {
                        const form = event.target;
                        const formData = new FormData(form);
                        const url = form.action;

                        try {
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            if (!response.ok) {
                                const err = await response.json().catch(() => ({}));
                                throw new Error(err.error || 'Submission failed');
                            }

                            const data = await response.json();
                            if (data.success) {
                                this.totals = { ...this.totals, ...data.totals };
                                if (['production', 'reject', 'input', 'rework'].includes(type)) {
                                    this.triggerBalanceFlash();
                                }

                                const tabMap = {
                                    'production': 'production',
                                    'reject': 'defects',
                                    'downtime': 'downtime',
                                    'rework': 'rework',
                                    'input': 'input',
                                    'manpower': 'manpower'
                                };

                                if (type === 'production') this.productionEntries.unshift(data.entry);
                                else if (type === 'reject') this.rejectEntries.unshift(data.entry);
                                else if (type === 'downtime') this.downtimeEntries.unshift(data.entry);
                                else if (type === 'rework') this.reworkEntries.unshift(data.entry);
                                else if (type === 'input') {
                                    this.inputEntries.unshift(data.entry);
                                    if (data.rework_entry) {
                                        this.reworkEntries.unshift(data.rework_entry);
                                    }
                                }
                                else if (type === 'manpower') {
                                    this.manpowerEntries.unshift(data.entry);
                                    window.dispatchEvent(new CustomEvent('team-count-changed', { detail: { count: this.manpowerEntries.length } }));
                                }

                                if (tabMap[type]) {
                                    this.tab = tabMap[type];
                                }

                                this.triggerHighlight(data.entry?.id);
                                this.recalcProgress();
                                form.reset();

                                const dataStack = form._x_dataStack || (form.closest('[x-data]') ? form.closest('[x-data]')._x_dataStack : null);
                                if (dataStack && dataStack[0]) {
                                    const alpineData = dataStack[0];
                                    if (alpineData) {
                                        if ('good_qty' in alpineData) alpineData.good_qty = 0;
                                        if ('reject_qty' in alpineData) alpineData.reject_qty = 0;
                                        if ('quantity' in alpineData) alpineData.quantity = 1;
                                        if ('input_qty' in alpineData) alpineData.input_qty = 0;
                                        if ('issue_qty' in alpineData) alpineData.issue_qty = 0;
                                        if ('recovered_qty' in alpineData) alpineData.recovered_qty = 0;
                                        if ('scrapped_qty' in alpineData) alpineData.scrapped_qty = 0;
                                        if ('qty' in alpineData) alpineData.qty = 0;
                                        if ('source' in alpineData) alpineData.source = 'wip';
                                        if ('defect' in alpineData) alpineData.defect = '';
                                        if ('reason' in alpineData) alpineData.reason = '';
                                    }
                                }

                                form.closest('dialog').close();
                            }
                        } catch (error) {
                            alert(error.message);
                        }
                    },

                    async submitMultiRejectForm(event) {
                        const form = event.target;
                        const alpineData = form._x_dataStack ? form._x_dataStack[0] : null;
                        if (!alpineData || !alpineData.validDefects || !alpineData.validDefects.length) {
                            alert('Please select quantity for at least one defect type.');
                            return;
                        }

                        const payload = {
                            defects: alpineData.validDefects,
                            remarks: alpineData.remarks || null
                        };

                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(payload)
                            });

                            const contentType = response.headers.get('content-type') || '';
                            let data = {};
                            if (contentType.includes('application/json')) {
                                data = await response.json();
                            } else {
                                const rawText = await response.text();
                                console.error('Non-JSON server response:', rawText);
                                throw new Error('Server error (' + response.status + '). Please try again.');
                            }

                            if (!response.ok) {
                                throw new Error(data.error || data.message || 'Failed to submit defects.');
                            }

                            if (data.success) {
                                if (data.totals) {
                                    this.totals = { ...this.totals, ...data.totals };
                                }
                                if (data.entries && data.entries.length) {
                                    data.entries.forEach(e => this.rejectEntries.unshift(e));
                                } else if (data.entry) {
                                    this.rejectEntries.unshift(data.entry);
                                }

                                this.tab = 'defects';
                                this.recalcProgress();
                                if (alpineData.resetCounts) alpineData.resetCounts();
                                form.closest('dialog').close();
                            }
                        } catch (error) {
                            alert(error.message);
                        }
                    },

                    formatTime(dateString) {
                        if (!dateString) return '-';
                        if (typeof dateString === 'string' && /^\d{2}:\d{2}(:\d{2})?$/.test(dateString.trim())) {
                            return dateString.trim();
                        }
                        let s = typeof dateString === 'string' ? dateString.trim() : dateString;
                        if (typeof s === 'string' && !s.includes('+') && !s.endsWith('Z')) {
                            s = s.replace(' ', 'T') + 'Z';
                        }
                        const d = new Date(s);
                        if (isNaN(d.getTime())) return typeof dateString === 'string' ? dateString : '-';
                        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit', hour12: false});
                    },
                    formatHM(dateString) {
                        if (!dateString) return '-';
                        if (typeof dateString === 'string' && /^\d{2}:\d{2}$/.test(dateString.trim())) {
                            return dateString.trim();
                        }
                        let s = typeof dateString === 'string' ? dateString.trim() : dateString;
                        if (typeof s === 'string' && !s.includes('+') && !s.endsWith('Z')) {
                            s = s.replace(' ', 'T') + 'Z';
                        }
                        const d = new Date(s);
                        if (isNaN(d.getTime())) return typeof dateString === 'string' ? dateString : '-';
                        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: false});
                    },
                    formatNum(num) {
                        return new Intl.NumberFormat().format(num || 0);
                    }
                }
            }

            function rejectBuilder(presetList) {
                return {
                    presetList: presetList || [],
                    stagedItems: [],
                    showCustom: false,
                    showCause: false,
                    customName: '',
                    cause: '',

                    getDefectCount(name) {
                        const item = this.stagedItems.find(i => i.defect_type.toLowerCase() === name.toLowerCase());
                        return item ? item.quantity : 0;
                    },

                    addDefect(name) {
                        if (!name || !name.trim()) return;
                        const cleanName = name.trim();
                        const existing = this.stagedItems.find(i => i.defect_type.toLowerCase() === cleanName.toLowerCase());
                        if (existing) {
                            existing.quantity += 1;
                        } else {
                            this.stagedItems.push({ defect_type: cleanName, quantity: 1 });
                        }
                    },

                    decrementDefect(name) {
                        const idx = this.stagedItems.findIndex(i => i.defect_type.toLowerCase() === name.toLowerCase());
                        if (idx !== -1) {
                            if (this.stagedItems[idx].quantity > 1) {
                                this.stagedItems[idx].quantity -= 1;
                            } else {
                                this.stagedItems.splice(idx, 1);
                            }
                        }
                    },

                    removeDefect(idx) {
                        this.stagedItems.splice(idx, 1);
                    },

                    addCustomDefect() {
                        if (this.customName && this.customName.trim()) {
                            this.addDefect(this.customName.trim());
                            this.customName = '';
                        }
                    },

                    get totalDefectQty() {
                        return this.stagedItems.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
                    },

                    get validDefects() {
                        return this.stagedItems.filter(item => (parseInt(item.quantity) || 0) > 0).map(item => ({
                            defect_type: item.defect_type,
                            quantity: parseInt(item.quantity),
                            cause: this.cause || null
                        }));
                    },

                    resetCounts() {
                        this.stagedItems = [];
                        this.customName = '';
                        this.cause = '';
                        this.showCustom = false;
                        this.showCause = false;
                    }
                };
            }
        </script>
