<template>
    <el-dialog
        title="Estados del pedido"
        :visible.sync="showDialog"
        width="680px"
        @open="getRecords"
        @close="close"
    >
        <!-- Subtítulo informativo -->
        <p class="so-subtitle">
            El orden define el flujo visible al cliente
        </p>

        <!-- Lista de estados via collapse -->
        <el-collapse v-model="activePanel" accordion class="so-collapse">
            <div
                v-for="(status, index) in statuses"
                :key="status.id"
                class="so-drag-wrapper"
                :class="{ 'so-dragging-ghost': status.id === draggedId }"
                @dragover.prevent="onDragOver(index)"
                @drop.prevent="onDrop()"
                @dragend="onDragEnd"
            >
            <el-collapse-item
                :name="String(status.id)"
                class="so-collapse-item"
            >
                <!-- Cabecera del ítem colapsado -->
                <template slot="title">
                    <div class="so-item-header">
                        <!-- Handle de arrastre -->
                        <span
                            class="so-drag-handle"
                            draggable="true"
                            @dragstart="onDragStart(index, $event)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-grip-vertical"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M8 5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M8 12a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M8 19a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M14 5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M14 12a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M14 19a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                        </span>
                        <!-- Dot de color -->
                        <span
                            class="so-color-dot"
                            :style="{ background: status.color || '#909399' }"
                        ></span>
                        <!-- Nombre del estado -->
                        <span class="so-item-name">{{ status.description }}</span>
                        <!-- Chips de acciones activas -->
                        <span class="so-chips">
                            <el-tag
                                v-for="action in activeActions(status)"
                                :key="action.key"
                                size="mini"
                                :color="action.color"
                                class="so-chip"
                            >{{ action.label }}</el-tag>
                            <el-tag v-if="status.is_payment_status" size="mini" type="success" class="so-chip text-gray">
                                Pago
                            </el-tag>
                            <el-tag v-else-if="status.is_shipping_status" size="mini" class="so-chip text-gray">
                                Envío
                            </el-tag>
                            <el-tag v-else size="mini" type="warning" class="so-chip text-gray">
                                Pedido
                            </el-tag>
                            <el-tag v-if="status.is_initial" size="mini" type="info" class="so-chip text-gray">
                                Estado inicial
                            </el-tag>
                        </span>
                    </div>
                </template>

                <!-- Contenido expandido: formulario de edición -->
                <div class="so-form px-2">
                    <!-- Nombre -->
                    <div class="so-field">
                        <label>Nombre del estado</label>
                        <el-input v-model="status.description" size="small"></el-input>
                    </div>

                    <!-- Paleta de colores -->
                    <div class="so-field">
                        <label>Color del estado</label>
                        <div class="so-color-palette">
                            <span
                                v-for="c in colorPalette"
                                :key="c"
                                class="so-palette-dot"
                                :class="{ 'so-palette-dot--active': status.color === c }"
                                :style="{ background: c }"
                                @click="status.color = c"
                            ></span>
                        </div>
                    </div>

                    <div class="so-field">
                        <label>Tipo de estado</label>
                        <div class="so-field--flags">
                            <el-checkbox
                                v-model="status.is_payment_status"
                                @change="setType(status, 'payment')"
                            >
                                Estado de pago
                            </el-checkbox>
                            <el-checkbox
                                v-model="status.is_order_status"
                                @change="setType(status, 'order')"
                            >
                                Estado de pedido
                            </el-checkbox>
                            <el-checkbox
                                v-model="status.is_shipping_status"
                                @change="setType(status, 'shipping')"
                            >
                                Estado de envío
                            </el-checkbox>
                        </div>
                        <span class="so-action-desc" style="padding-left: 0">
                            Define en qué columna del listado de pedidos aparece este estado.
                        </span>
                    </div>

                    <!-- Acciones automáticas -->
                    <div class="so-field">
                        <label class="so-actions-label">ACCIONES AUTOMÁTICAS DEL SISTEMA</label>
                        <div class="so-actions-grid">
                            <div
                                v-for="action in actionDefs"
                                :key="action.key"
                                class="so-action-item"
                                :class="{ 'so-action-item--disabled': action.disabled }"
                            >
                                <el-checkbox
                                    v-model="status[action.key]"
                                    :disabled="action.disabled"
                                >
                                    <span class="so-action-name">{{ action.label }}</span>
                                </el-checkbox>
                                <span class="so-action-desc">{{ action.desc }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Estado inicial -->
                    <div class="so-field">
                        <el-checkbox
                            v-model="status.is_initial"
                            @change="setInitial(status)"
                        >
                            Usar como estado inicial ({{ status.is_payment_status ? 'de pago' : (status.is_shipping_status ? 'de envío' : 'de pedido') }})
                        </el-checkbox>
                    </div>

                    <!-- Acciones del panel -->
                    <div class="so-panel-actions">
                        <el-button
                            type="danger"
                            size="mini"
                            plain
                            @click="destroy(status)"
                        >
                            <i class="el-icon-delete"></i> Eliminar
                        </el-button>
                        <el-button
                            type="primary"
                            size="small"
                            :loading="saving === status.id"
                            @click="update(status)"
                        >
                            Listo
                        </el-button>
                    </div>
                </div>
            </el-collapse-item>
            </div>
        </el-collapse>

        <!-- Pie del dialog: agregar nuevo estado -->
        <div slot="footer" class="so-footer">
            <el-input
                v-model="newDescription"
                placeholder="Nombre del nuevo estado..."
                size="small"
                style="width: 300px"
                @keyup.enter.native="store"
            ></el-input>
            <el-button
                type="primary"
                size="small"
                :loading="storing"
                @click="store"
            >
                <i class="el-icon-plus"></i> Nuevo estado
            </el-button>
        </div>
    </el-dialog>
</template>

<style scoped>
.so-subtitle {
    font-size: 12px;
    color: #909399;
    margin: -10px 0 12px;
}
.so-collapse {
    border-top: 1px solid #ebeef5;
}
/* Forzar que el título del collapse ocupe todo el ancho */
.so-collapse :deep(.el-collapse-item__header),
.so-collapse >>> .el-collapse-item__header {
    height: auto;
    min-height: 48px;
    padding: 6px 12px;
    line-height: 1.4;
}
.so-item-header {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
    padding-right: 8px;
}
.so-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}
.so-item-name {
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.so-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    flex: 1;
    min-width: 0;
}
.so-chip {
    border: none !important;
    color: #fff !important;
    font-size: 11px;
}
.so-drag-wrapper {
    position: relative;
}
.so-dragging-ghost {
    opacity: 0.4;
}
.so-drag-handle {
    cursor: move;
    color: #909399;
    user-select: none;
}
.so-drag-handle:active {
    cursor: grabbing;
}
/* Formulario expandido */
.so-form {
    padding: 4px 0 0;
}
.so-field {
    margin-bottom: 16px;
}
.so-field > label {
    display: block;
    font-size: 12px;
    color: #606266;
    margin-bottom: 6px;
}
.so-field--flags {
    display: flex;
    gap: 24px;
    margin-bottom: 6px;
}
/* Paleta de colores */
.so-color-palette {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.so-palette-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid transparent;
    transition: transform .15s, border-color .15s;
}
.so-palette-dot:hover {
    transform: scale(1.15);
}
.so-palette-dot--active {
    border-color: #303133;
    transform: scale(1.15);
}
/* Grid de acciones */
.so-actions-label {
    font-size: 11px !important;
    color: #909399 !important;
    letter-spacing: .5px;
    text-transform: uppercase;
}
.so-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 16px;
    margin-top: 4px;
}
.so-action-item {
    border: 1px solid #ebeef5;
    border-radius: 6px;
    padding: 8px 10px;
    background: #fafafa;
}
.so-action-item--disabled {
    opacity: .5;
    cursor: not-allowed;
}
.so-action-name {
    font-weight: 500;
    font-size: 13px;
}
.so-action-desc {
    display: block;
    font-size: 11px;
    color: #909399;
    margin-top: 2px;
    padding-left: 22px;
}
/* Acciones del panel (footer interno) */
.so-panel-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 8px;
    border-top: 1px solid #ebeef5;
    margin-top: 4px;
}
/* Footer del dialog */
.so-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: flex-end;
}
.el-collapse.so-collapse {
    border: none !important;
}
</style>
<style>
.so-collapse-item .el-collapse-item__header{
    border: none !important;
}
.so-collapse-item .el-collapse-item__content {
    padding-bottom: 5px !important;
}
</style>

<script>
export default {
    props: {
        showDialog: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            statuses: [],
            activePanel: null,
            newDescription: '',
            storing: false,
            saving: null,
            draggedId: null,
            originalStatuses: null,
            dropped: false,

            // Paleta de 15 colores vibrantes, distribuidos en todo el espectro
            colorPalette: [
                '#64748B', // gris pizarra
                '#EF4444', // rojo
                '#F97316', // naranja
                '#F59E0B', // ámbar
                '#EAB308', // amarillo
                '#84CC16', // lima
                '#22C55E', // verde
                '#10B981', // esmeralda
                '#14B8A6', // teal
                '#06B6D4', // cian
                '#3B82F6', // azul
                '#6366F1', // índigo
                '#8B5CF6', // violeta
                '#D946EF', // fucsia
                '#EC4899', // rosa
            ],

            // Definición de acciones: label, descripción, clave del modelo y si está deshabilitada
            actionDefs: [
                { key: 'action_discount_stock',     label: 'Descontar stock',       desc: 'Reduce el inventario disponible',      disabled: false, color: '#E6A23C' },
                { key: 'action_mark_payment',       label: 'Marcar pago recibido',  desc: 'Registra el pago como confirmado',     disabled: true, color: '#67C23A' },
                { key: 'action_generate_document',  label: 'Generar comprobante',   desc: 'Emite factura o boleta electrónica',   disabled: false, color: '#409EFF' },
                { key: 'action_send_email',         label: 'Enviar email al cliente', desc: 'Notifica el cambio de estado',       disabled: false, color: '#8B5CF6' },
                { key: 'action_notify_dispatch',    label: 'Notificar despacho',    desc: 'Alerta al área logística',             disabled: true, color: '#409EFF' },
                { key: 'action_generate_remission', label: 'Generar guía de remisión', desc: 'Crea el documento de envío',       disabled: true,  color: '#10B981' },
                { key: 'action_free_reserved_stock',label: 'Liberar stock reservado', desc: 'Devuelve unidades al inventario',   disabled: true,  color: '#E6A23C' },
                { key: 'action_block_returns',      label: 'Bloquear devoluciones', desc: 'Cierra el periodo de cambio',          disabled: true,  color: '#F56C6C' },
                { key: 'action_void_order',         label: 'Anular pedido',         desc: 'Cancela y revierte el pedido',         disabled: true,  color: '#F56C6C' },
            ],
        }
    },

    computed: {
        // Campos enviados en store/update (todos los editables)
        formFields() {
            return [
                'description', 'color', 'is_initial',
                'is_payment_status', 'is_order_status', 'is_shipping_status',
                'action_discount_stock', 'action_mark_payment',
                'action_generate_document', 'action_send_email',
                'action_notify_dispatch', 'action_generate_remission',
                'action_free_reserved_stock', 'action_block_returns',
                'action_void_order',
            ]
        },
    },

    methods: {
        // Devuelve las acciones activas de un estado para mostrar como chips
        activeActions(status) {
            return this.actionDefs.filter(a => status[a.key])
        },

        getRecords() {
            this.$http.get('/statusOrder/records').then(response => {
                // Normalizar booleanos que pueden llegar como 0/1 desde el backend
                this.statuses = response.data.map(s => this.normalize(s))
                this.activePanel = null
            })
        },

        // Garantiza que los booleanos sean boolean (no 0/1)
        normalize(status) {
            const booleans = [
                'is_initial', 'is_payment_status', 'is_order_status', 'is_shipping_status',
                'action_discount_stock', 'action_mark_payment',
                'action_generate_document', 'action_send_email', 'action_notify_dispatch',
                'action_generate_remission', 'action_free_reserved_stock',
                'action_block_returns', 'action_void_order',
            ]
            const s = { ...status }
            booleans.forEach(k => { s[k] = Boolean(s[k]) })
            return s
        },

        store() {
            if (!this.newDescription.trim())
                return this.$message.error('Ingrese un nombre para el estado')

            this.storing = true
            this.$http.post('/statusOrder/store', { description: this.newDescription })
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                        this.newDescription = ''
                        this.getRecords()
                    }
                })
                .finally(() => { this.storing = false })
        },

        update(status) {
            if (!status.description.trim())
                return this.$message.error('Ingrese un nombre para el estado')

            this.saving = status.id
            const payload = {}
            this.formFields.forEach(f => { payload[f] = status[f] })

            this.$http.put(`/statusOrder/update/${status.id}`, payload)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                        this.activePanel = null
                        this.getRecords()
                    }
                })
                .finally(() => { this.saving = null })
        },

        // Grupo de un estado: 'payment' | 'shipping' | 'order'
        typeOf(s) {
            if (s.is_payment_status) return 'payment'
            if (s.is_shipping_status) return 'shipping'
            return 'order'
        },

        // Marca un estado como inicial. El inicial es único por tipo (pago, pedido o envío),
        // así que solo se desactiva en los demás estados del mismo grupo.
        setInitial(status) {
            if (!status.is_initial) return
            const group = this.typeOf(status)
            this.statuses.forEach(s => {
                if (s.id !== status.id && this.typeOf(s) === group) {
                    s.is_initial = false
                }
            })
        },

        // Tipo de estado: pago y pedido son mutuamente excluyentes.
        // Siempre debe quedar exactamente uno marcado.
        setType(status, type) {
            // Radio-like entre 3 grupos: el que se tocó manda, se apagan los otros dos.
            status.is_payment_status  = type === 'payment'  ? status.is_payment_status  : false
            status.is_order_status    = type === 'order'    ? status.is_order_status    : false
            status.is_shipping_status = type === 'shipping' ? status.is_shipping_status : false
            // Garantizar que al menos uno quede activo (por defecto pedido)
            if (!status.is_payment_status && !status.is_order_status && !status.is_shipping_status) {
                status.is_order_status = true
            }
        },

        destroy(status) {
            this.$confirm(
                `¿Desea eliminar el estado "${status.description}"?`,
                'Eliminar estado',
                { confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar', type: 'warning' }
            ).then(() => {
                this.$http.delete(`/statusOrder/destroy/${status.id}`)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            this.getRecords()
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
            }).catch(() => {})
        },

        onDragStart(index, event) {
            const id = this.statuses[index].id
            this.originalStatuses = [...this.statuses]
            this.dropped = false
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', String(index))
            const wrapper = event.target.closest('.so-drag-wrapper')
            if (wrapper) event.dataTransfer.setDragImage(wrapper, 20, 20)
            // Set after ghost is captured so the native drag image looks fully opaque
            requestAnimationFrame(() => { this.draggedId = id })
        },

        onDragOver(index) {
            if (this.draggedId === null) return
            const currentIndex = this.statuses.findIndex(s => s.id === this.draggedId)
            if (currentIndex === -1 || currentIndex === index) return
            const list = [...this.statuses]
            const [moved] = list.splice(currentIndex, 1)
            list.splice(index, 0, moved)
            this.statuses = list
        },

        onDrop() {
            if (this.draggedId === null) return
            this.dropped = true
            this.draggedId = null
            this.originalStatuses = null
            this.persistOrder()
        },

        onDragEnd() {
            if (!this.dropped && this.originalStatuses) {
                this.statuses = [...this.originalStatuses]
            }
            this.draggedId = null
            this.originalStatuses = null
            this.dropped = false
        },

        // Envía el nuevo sort_order de todos los estados al backend
        persistOrder() {
            const order = this.statuses.map((s, i) => ({ id: s.id, sort_order: i }))
            this.$http.post('/statusOrder/reorder', { order })
                .then(response => {
                    if (!response.data.success) {
                        this.$message.error('Error al guardar el orden')
                        this.getRecords() // revertir al orden de DB si falla
                    }
                })
                .catch(() => {
                    this.$message.error('Error al guardar el orden')
                    this.getRecords()
                })
        },

        close() {
            this.activePanel = null
            this.$emit('update:showDialog', false)
            this.$eventHub.$emit('statusesUpdated')
        }
    }
}
</script>