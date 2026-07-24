<template>
    <div class="cash">
        <div class="page-header pe-0">
            <h2>
                <a href="/cash">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        style="margin-top: -5px;"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-calculator"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"
                        />
                        <path
                            d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"
                        />
                        <path d="M8 14l0 .01" />
                        <path d="M12 14l0 .01" />
                        <path d="M16 14l0 .01" />
                        <path d="M8 17l0 .01" />
                        <path d="M12 17l0 .01" />
                        <path d="M16 17l0 .01" />
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Cajas chicas</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <template v-if="open_cash">
                    <button
                        type="button"
                        class="btn btn-custom btn-sm  mt-2 me-2"
                        @click.prevent="openAuditPanel()"
                    >
                        <i class="fas fa-chart-line"></i> Auditoría Avanzada
                    </button>

                    <button
                        type="button"
                        class="btn btn-custom btn-sm  mt-2 me-2"
                        @click.prevent="clickCreate()"
                    >
                        <i class="fas fa-shopping-cart"></i> Aperturar caja chica
                    </button>
                </template>
                <!-- <template v-else>                 -->
                <!-- <button type="button" class="btn btn-success btn-sm  mt-2 me-2" @click.prevent="clickOpenPos()"><i class="fas fa-shopping-cart" ></i> Aperturar punto de venta</button> -->
                <!-- </template> -->
            </div>
        </div>
        <div class="card tab-content-default row-new mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de cajas</h3>
            </div> -->
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th>Referencia</th>
                        <th>Vendedor</th>
                        <th class="text-start">Apertura</th>
                        <th class="text-start">Cierre</th>
                        <th class="text-end">Saldo inicial</th>
                        <th class="text-end">Saldo final</th>
                        <!-- <th>Ingreso</th> -->
                        <!-- <th>Egreso</th> -->
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>

                    <tr></tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td>{{ row.reference_number }}</td>
                        <td>
                            {{ row.user }}
                            <br>
                            <small class="text-muted">{{ row.user_email }}</small>
                        </td>
                        <td class="text-start">{{ formatDate(row.opening) }}</td>
                        <td class="text-start">{{ formatDate(row.closed) }}</td>
                        <td class="text-end">{{ row.beginning_balance }}</td>
                        <td class="text-end">{{ row.final_balance }}</td>
                        <!-- <td>{{ row.income }}</td>
                        <td>{{ row.expense }}</td> -->
                        <td>{{ row.state_description }}</td>
                        <td class="text-end">
                            <template v-if="availableReportForSeller">
                            <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickDownload(row.id)">Reporte</button> -->

                            <div class="btn-group flex-wrap">
                                <el-dropdown trigger="click">
                                    <button
                                        type="button"
                                        class="btn waves-effect waves-light btn-xs btn-primary dropdown-toggle me-1"
                                        aria-expanded="false"
                                    >
                                        Reporte <span class="caret"></span>
                                    </button>
                                    <el-dropdown-menu slot="dropdown">
                                        <el-dropdown-item @click.native.prevent="clickDownloadReport(row.id, 'a4')">PDF A4</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickDownloadReport(row.id, 'ticket')">PDF Ticket</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickDownloadReport(row.id, 'ticket', '58')">PDF Ticket 58</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickDownloadReport(row.id, 'ticket', '80', 1)">PDF Ticket Resumen</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickDownloadReport(row.id, 'simple_a4')">Simple A4</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickDownloadReport(row.id, 'excel')">Excel</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickReportSummaryDailyOperations(row.id)">Resumen de Operaciones Diarias</el-dropdown-item>
                                        <el-tooltip
                                            class="item"
                                            content="Reporte general de caja asociado a los pagos al contado con destino caja"
                                            effect="dark"
                                            placement="right-end"
                                        >
                                            <el-dropdown-item @click.native.prevent="clickReportCashWithPayments(row.id)">Reporte general caja V2</el-dropdown-item>
                                        </el-tooltip>
                                    </el-dropdown-menu>
                                </el-dropdown>
                            </div>

                            <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickDownloadProducts(row.id)">Reporte Productos</button> -->

                            <div class="btn-group flex-wrap">
                                <el-dropdown trigger="click">
                                    <button
                                        type="button"
                                        class="btn waves-effect waves-light btn-xs btn-primary dropdown-toggle me-1"
                                        aria-expanded="false"
                                    >
                                        Reporte Efectivo <span class="caret"></span>
                                    </button>
                                    <el-dropdown-menu slot="dropdown">
                                        <el-tooltip
                                            class="item"
                                            content="Ingresos en efectivo con destino caja"
                                            effect="dark"
                                            placement="right-end"
                                        >
                                            <el-dropdown-item @click.native.prevent="clickCashPaymentReportExcel(row.id)">Excel</el-dropdown-item>
                                        </el-tooltip>
                                        <el-dropdown-item @click.native.prevent="clickDownloadReportIncomeEgress(row.id)">Ingresos y egresos</el-dropdown-item>
                                        <el-tooltip
                                            class="item"
                                            content="Ingresos en efectivo con destino caja - Disponible para facturas, boletas y notas de venta"
                                            effect="dark"
                                            placement="right-end"
                                        >
                                            <el-dropdown-item @click.native.prevent="clickReportPaymentsAssociatedCash(row.id)">Pagos asociados a caja</el-dropdown-item>
                                        </el-tooltip>
                                    </el-dropdown-menu>
                                </el-dropdown>
                            </div>

                            <div class="btn-group flex-wrap">
                                <el-dropdown trigger="click">
                                    <button
                                        type="button"
                                        class="btn waves-effect waves-light btn-xs btn-primary dropdown-toggle me-1"
                                        aria-expanded="false"
                                    >
                                        Reporte Productos
                                        <span class="caret"></span>
                                    </button>
                                    <el-dropdown-menu slot="dropdown">
                                        <el-dropdown-item @click.native.prevent="clickDownloadProducts(row.id, 'pdf')">Punto de venta - PDF</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickDownloadProducts(row.id, 'excel')">Punto de venta - Excel</el-dropdown-item>
                                        <el-dropdown-item @click.native.prevent="clickDownloadProducts(row.id, 'pdf', true)">Venta rápida - PDF</el-dropdown-item>
                                    </el-dropdown-menu>
                                </el-dropdown>
                            </div>

                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-success me-1"
                                @click.prevent="
                                    clickDownloadIncomeSummary(row.id)
                                "
                            >
                                R. Ingreso
                            </button>
                            </template>

                            <template v-if="row.state">
                                <button
                                    type="button"
                                    class="btn waves-effect waves-light btn-xs btn-warning me-1"
                                    @click.prevent="clickCloseCash(row.id)"
                                >
                                    Cerrar caja
                                </button>
                                <button
                                    v-if="typeUser === 'admin'"
                                    type="button"
                                    class="btn waves-effect waves-light btn-xs btn-info me-1"
                                    @click.prevent="clickCreate(row.id)"
                                >
                                    Editar
                                </button>
                                <button
                                    v-if="typeUser === 'admin'"
                                    type="button"
                                    class="btn waves-effect waves-light btn-xs btn-danger me-1"
                                    @click.prevent="clickDelete(row.id)"
                                >
                                    Eliminar
                                </button>
                            </template>

                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-info me-1"
                                @click.prevent="clickOptions(row.id)"
                            >
                                C. Electrónico
                            </button>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>
        <cash-form
            :showDialog.sync="showDialog"
            :typeUser="typeUser"
            :recordId="recordId"
        ></cash-form>

        <cash-options
            :showDialog.sync="showDialogOptions"
            :recordId="recordId"
        ></cash-options>
<el-dialog title="Dashboard de Auditoría y Control Forense" :visible.sync="showAuditDialog" width="90%" top="5vh">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="control-label font-weight-bold text-info">1. Seleccione Caja a Auditar</label>
                    <el-select v-model="audit_cash_id" @change="loadTransactions" placeholder="Buscar caja por fecha o responsable..." class="w-100" filterable clearable>
                        <el-option v-for="box in all_boxes" :key="box.id" :label="box.description" :value="box.id"></el-option>
                    </el-select>
                </div>

                <div class="col-md-8 mb-3">
                    <label class="control-label font-weight-bold text-info">2. Filtros de Búsqueda de Transacciones</label>
                    <div class="d-flex flex-wrap" style="gap: 10px;">

                        <el-date-picker
                            v-model="filter_date_range"
                            type="daterange"
                            range-separator="a"
                            start-placeholder="Fecha inicio"
                            end-placeholder="Fecha fin"
                            format="dd/MM/yyyy"
                            value-format="yyyy-MM-dd"
                            :disabled="!audit_cash_id"
                            style="flex: 1; min-width: 220px;">
                        </el-date-picker>

                        <el-select v-model="filter_user" placeholder="Responsable" :disabled="!audit_cash_id" style="flex: 1; min-width: 130px;" clearable>
                            <el-option v-for="usr in available_users" :key="usr" :label="usr" :value="usr"></el-option>
                        </el-select>

                        <el-select v-model="filter_method" placeholder="Método de Pago" :disabled="!audit_cash_id" style="flex: 1; min-width: 130px;" clearable>
                            <el-option v-for="met in available_methods" :key="met" :label="met" :value="met"></el-option>
                        </el-select>

                        <el-input v-model="filter_document" placeholder="Buscar N° Documento" :disabled="!audit_cash_id" style="flex: 1; min-width: 160px;" clearable></el-input>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-12">
                    <div
                        class="table-responsive"
                        style="min-height: 55vh; max-height: 65vh; overflow-y: auto; border: 1px solid #ebeef5;"
                        v-loading="loading_audit"
                        element-loading-text="Cargando transacciones de auditoría..."
                        element-loading-spinner="el-icon-loading"
                        element-loading-background="rgba(255, 255, 255, 0.85)"
                    >
                        <table class="table table-bordered table-striped table-sm text-center m-0">
                            <thead style="background-color: #f8f9fa; color: #333; position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha y hora de emisión</th>
                                    <th>Tipo de transacción</th>
                                    <th>Responsable</th>
                                    <th>Documento</th>
                                    <th>Cliente/Proveedor</th>
                                    <th>N° Documento</th>
                                    <th>Método de Pago</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!audit_cash_id">
                                    <td colspan="9" class="text-center text-muted" style="padding-top: 15vh; border-bottom: none;">
                                        <i class="fas fa-search fa-3x mb-3 text-info"></i><br>
                                        <span class="font-weight-bold" style="font-size: 1.2rem;">Seleccione una caja en el paso 1</span><br>
                                        para desplegar el historial de movimientos...
                                    </td>
                                </tr>

                                <tr v-for="(tr, index) in filteredTransactions" :key="tr.id" v-else>
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ tr.datetime }}</td>
                                    <td>
                                        <span :class="{'text-danger font-weight-bold': tr.amount < 0}">{{ tr.type }}</span>
                                    </td>
                                    <td>{{ tr.responsable }}</td>
                                    <td>{{ tr.document }}</td>
                                    <td>{{ tr.customer_name }}</td>
                                    <td>{{ tr.customer_number }}</td>
                                    <td>{{ tr.method }}</td>
                                    <td :class="{'text-danger font-weight-bold': tr.amount < 0}">S/ {{ tr.amount.toFixed(2) }}</td>
                                </tr>

                                <tr v-if="audit_cash_id && filteredTransactions.length === 0">
                                    <td colspan="9" class="text-center text-danger py-4 font-weight-bold">
                                        <i class="fas fa-exclamation-circle"></i> No hay transacciones que coincidan con los filtros.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <span slot="footer" class="dialog-footer d-flex justify-content-between align-items-center w-100" style="background-color: #f8f9fa; padding: 15px; border-radius: 0 0 8px 8px; border-top: 1px solid #ebeef5; margin-top: -15px;">

                <div class="text-start" v-if="audit_cash_id && filteredTransactions.length > 0">
                    <span class="font-weight-bold text-muted me-3" style="font-size: 0.95rem;">
                        <i class="fas fa-chart-pie text-info"></i> Resumen de la vista actual:
                    </span>

                    <span v-for="item in paymentMethodSummary" :key="item.method" class="badge bg-white text-dark border border-secondary me-2 p-2 shadow-sm" style="font-size: 0.85rem; letter-spacing: 0.3px;">
                        {{ item.method }}:
                        <span :class="{'text-danger': item.total < 0, 'text-success': item.total >= 0}" class="font-weight-bold" style="font-size: 0.95rem;">
                            S/ {{ item.total.toFixed(2) }}
                        </span>
                    </span>
                </div>

                <div v-else-if="audit_cash_id && all_transactions.length === 0" class="text-muted font-italic" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i> Esta caja no tiene transacciones registradas.
                </div>

                <div v-else-if="audit_cash_id && filteredTransactions.length === 0" class="text-muted font-italic" style="font-size: 0.85rem;">
                    <i class="fas fa-filter"></i> Los filtros actuales no muestran transacciones.
                </div>

                <div v-else class="text-muted font-italic" style="font-size: 0.85rem;">
                    <i class="fas fa-hand-pointer"></i> Seleccione una caja para calcular resumen...
                </div>

                <div>
                    <el-button
                        type="success"
                        size="small"
                        @click="exportCSV()"
                        :disabled="!audit_cash_id || filteredTransactions.length === 0"
                        class="shadow-sm me-2"
                    >
                        <i class="fas fa-file-csv"></i> Exportar a CSV
                    </el-button>
                    <el-button type="danger" size="small" @click="showAuditDialog = false" class="shadow-sm">
                        <i class="fas fa-times"></i> Cerrar Panel
                    </el-button>
                </div>
            </span>
            </el-dialog>
    </div>
</template>
<style>
@media only screen and (max-width: 485px) {
    .filter-container {
        margin-top: 0px;
        & .btn-filter-content,
        .btn-container-mobile {
            display: flex;
            align-items: center;
            justify-content: start;
        }
    }
}
</style>
<script>
import DataTable from "../../../components/DataTable.vue";
import { deletable } from "../../../mixins/deletable";
import CashForm from "./form.vue";
import CashOptions from "./partials/options.vue";

export default {
    mixins: [deletable],
    components: { DataTable, CashForm, CashOptions },
    props: ["typeUser", "configuration"],
    data() {
        return {
            showAuditDialog: false,
            audit_cash_id: null,
            all_boxes: [],
            filter_user: null,
            filter_type: null,
            filter_document: null,
            filter_method: null,
            filter_date_range: null,

            all_transactions: [],
            available_users: [],
            available_methods: [],

            audit_results: [],
            loading_audit: false,
            showDialog: false,
            showDialogOptions: false,
            open_cash: true,
            resource: "cash",
            recordId: null,
            cash: null
        };
    },
    async created() {
        /*await this.$http.get(`/${this.resource}/opening_cash`)
                .then(response => {
                    this.cash = response.data.cash
                    this.open_cash = (this.cash) ? false : true
                })*/
        /*this.$eventHub.$on('openCash', () => {
                this.open_cash = false
            })*/
        
        // Verificar si se redirigió por falta de caja
        this.checkRedirectReason();
    },
    computed: {

        filteredTransactions() {
            return this.all_transactions.filter(t => {
                let matchUser = !this.filter_user || t.responsable === this.filter_user;

                // 🚀 ESTA LÍNEA ES LA QUE HACE FUNCIONAR EL NUEVO FILTRO
                let matchMethod = !this.filter_method || t.method === this.filter_method;

                let matchDoc = !this.filter_document || t.document.toLowerCase().includes(this.filter_document.toLowerCase());

                let matchDate = true;
                if (this.filter_date_range && this.filter_date_range.length === 2) {
                    let txDateFormatted = t.datetime.substring(0, 10).split('/').reverse().join('-');
                    let start = this.filter_date_range[0];
                    let end = this.filter_date_range[1];
                    matchDate = (txDateFormatted >= start && txDateFormatted <= end);
                }

                // Y asegúrate de que el return devuelva todos los match:
                return matchUser && matchMethod && matchDoc && matchDate;
            });
        },

        paymentMethodSummary() {
            let summary = {};

            // Sumamos los montos agrupándolos por método de pago
            this.filteredTransactions.forEach(t => {
                if (!summary[t.method]) summary[t.method] = 0;
                summary[t.method] += parseFloat(t.amount);
            });

            // Lo convertimos en un arreglo para poder dibujarlo
            let result = [];
            for (let method in summary) {
                // Redondeamos para evitar fallos de decimales como 0.0000001
                let total = Math.round(summary[method] * 100) / 100;
                if (total !== 0) { // Ocultamos métodos que queden en 0 exacto
                    result.push({ method: method, total: total });
                }
            }

            // Los ordenamos de mayor a menor monto
            return result.sort((a, b) => b.total - a.total);
        },

        availableReportForSeller() {
            return this.typeUser === 'admin' ? true : (this.typeUser === 'seller' && this.configuration.available_cash_report_seller ? true : false);
        }
    },
    methods: {

        loadTransactions() {
            // Limpiamos todo al cambiar de caja
            this.all_transactions = [];
            this.available_users = [];
            this.available_methods = [];
            this.filter_user = null;
            this.filter_document = null;
            this.filter_method = null;
            this.filter_date_range = null;

            if (!this.audit_cash_id) return; // Si limpia el buscador, no hace nada

            this.loading_audit = true;
            this.$http.get(`/cash/get-audit-transactions/${this.audit_cash_id}`)
                .then(response => {
                    if (response.data.success) {
                        this.all_transactions = response.data.data;

                        // Extraemos los responsables únicos para el filtro
                        let users = new Set(this.all_transactions.map(t => t.responsable));
                        this.available_users = Array.from(users);

                        // 🚀 EXTRAEMOS LOS MÉTODOS DE PAGO ÚNICOS PARA EL FILTRO
                        let methods = new Set(this.all_transactions.map(t => t.method));
                        this.available_methods = Array.from(methods);
                    }
                })
                .catch(error => { console.log(error); this.$message.error('Error al cargar transacciones'); })
                .finally(() => { this.loading_audit = false; });
        },

        openAuditPanel() {
            this.showAuditDialog = true;
            this.loadAuditBoxes(); // Llamamos a la base de datos al abrir
        },
        loadAuditBoxes() {
            this.$http.get(`/cash/get-boxes-audit`)
                .then(response => {
                    if (response.data.success) {
                        this.all_boxes = response.data.data;
                    }
                })
                .catch(error => {
                    console.log(error);
                    this.$message.error('Error al cargar historial de cajas');
                });
        },

        /**
         * Exporta filteredTransactions a CSV y dispara descarga vía Blob.
         * Feature nueva (no existe en pro8 commit 19e8fc86) — solicitada por Carlos 2026-07-21.
         */
        exportCSV() {
            if (!this.filteredTransactions || this.filteredTransactions.length === 0) {
                this.$message.warning('No hay transacciones para exportar.');
                return;
            }

            const headers = [
                '#',
                'Fecha y hora de emisión',
                'Tipo de transacción',
                'Responsable',
                'Documento',
                'Cliente/Proveedor',
                'N° Documento',
                'Método de Pago',
                'Monto'
            ];

            const escapeCsv = (value) => {
                if (value === null || value === undefined) return '';
                const str = String(value);
                // Escapar comillas dobles y envolver si contiene comas, saltos o comillas
                if (/[",\n\r]/.test(str)) {
                    return '"' + str.replace(/"/g, '""') + '"';
                }
                return str;
            };

            const rows = this.filteredTransactions.map((tr, index) => [
                index + 1,
                tr.datetime || '',
                tr.type || '',
                tr.responsable || '',
                tr.document || '',
                tr.customer_name || '',
                tr.customer_number || '',
                tr.method || '',
                (typeof tr.amount === 'number') ? tr.amount.toFixed(2) : (tr.amount || '')
            ]);

            const csvContent = [headers, ...rows]
                .map(row => row.map(escapeCsv).join(','))
                .join('\n');

            // BOM para que Excel reconozca UTF-8
            const bom = '﻿';
            const blob = new Blob([bom + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            const cashLabel = this.all_boxes.find(b => b.id === this.audit_cash_id);
            const cashName = cashLabel ? cashLabel.description.replace(/[^\w\-]+/g, '_') : `caja_${this.audit_cash_id}`;
            const filename = `auditoria_${cashName}_${new Date().toISOString().slice(0, 10)}.csv`;

            link.href = url;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            this.$message.success(`Exportado: ${filename} (${rows.length} transacciones)`);
        },
        checkRedirectReason() {
            const urlParams = new URLSearchParams(window.location.search);
            const redirectReason = urlParams.get('redirect_reason');
            
            if (redirectReason === 'no_cash_sale_note') {
                this.$message({
                    message: 'Debe aperturar una caja antes de crear una nota de venta.',
                    type: 'warning',
                    duration: 5000
                });
                this.clearUrlParameters();
            } else if (redirectReason === 'no_cash_document') {
                this.$message({
                    message: 'Debe aperturar una caja antes de crear un nuevo comprobante.',
                    type: 'warning',
                    duration: 5000
                });
                this.clearUrlParameters();
            } else if (redirectReason === 'no_cash_pos') {
                this.$message({
                    message: 'Debe aperturar una caja antes de acceder al punto de venta.',
                    type: 'warning',
                    duration: 5000
                });
                this.clearUrlParameters();
            } else if (redirectReason === 'no_cash_fast_sale') {
                this.$message({
                    message: 'Debe aperturar una caja antes de acceder a la venta rápida.',
                    type: 'warning',
                    duration: 5000
                });
                this.clearUrlParameters();
            } else if (redirectReason === 'no_cash_garage') {
                this.$message({
                    message: 'Debe aperturar una caja antes de acceder a la venta rápida.',
                    type: 'warning',
                    duration: 5000
                });
                this.clearUrlParameters();
            }
        },
        clearUrlParameters() {
            const url = new URL(window.location);
            url.searchParams.delete('redirect_reason');
            window.history.replaceState({}, document.title, url.pathname);
        },
        formatDate(date) {
            if (!date) return null;
            const parsedDate = moment(date);
            return parsedDate.isValid()
                ? parsedDate.format("DD-MM-YYYY h:mmA")
                : null;
        },
        clickOptions(recordId) {
            this.showDialogOptions = true;
            this.recordId = recordId;
        },
        clickDownloadReport(id, template, mm = 80, summary = 0) {
            if (template == "ticket") {
                window.open(
                    `/${
                        this.resource
                    }/report-${template}/${id}/${mm}/${summary}`,
                    "_blank"
                );
            } else if (template == "simple_a4") {
                window.open(
                    `/${this.resource}/simple/report-a4/${id}/`,
                    "_blank"
                );
            } else {
                window.open(
                    `/${this.resource}/report-${template}/${id}`,
                    "_blank"
                );
            }
        },
        clickDownload(id) {
            window.open(`/${this.resource}/report/${id}`, "_blank");
        },
        clickDownloadIncomeSummary(id) {
            window.open(
                `/${this.resource}/report/income-summary/${id}`,
                "_blank"
            );
        },
        clickCreate(recordId = null) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        clickCloseCash(recordId) {
            this.recordId = recordId;
            const h = this.$createElement;
            this.$msgbox({
                title: "Cerrar caja chica POS",
                type: "warning",
                message: h("p", null, [
                    h(
                        "p",
                        { style: "text-align: justify; font-size:15px" },
                        "¿Está seguro de cerrar la caja?"
                    )
                ]),

                showCancelButton: true,
                confirmButtonText: "Cerrar",
                cancelButtonText: "Cancelar",
                beforeClose: (action, instance, done) => {
                    if (action === "confirm") {
                        this.createRegister(instance, done);
                    } else {
                        done();
                    }
                }
            })
                .then(action => {})
                .catch(action => {});
        },
        createRegister(instance, done) {
            instance.confirmButtonLoading = true;
            instance.confirmButtonText = "Cerrando caja...";

            this.$http
                .get(`/${this.resource}/close/${this.recordId}`)
                .then(response => {
                    if (response.data.success) {
                        this.$eventHub.$emit("reloadData");
                        this.open_cash = true;
                        this.$message.success(response.data.message);
                    } else {
                        console.log(response);
                        this.$message.success(response.data.message);
                    }
                })
                .catch(error => {
                    console.log(error);
                })
                .then(() => {
                    instance.confirmButtonLoading = false;
                    instance.confirmButtonText = "Iniciar prueba";
                    done();
                });
        },
        clickOpenPos() {
            window.open("/pos");
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        clickDownloadProducts(id, type, is_garage = false) {
            if (type == "excel") {
                window.open(
                    `/${this.resource}/report/products-excel/${id}`,
                    "_blank"
                );
                return;
            }

            window.open(
                `/${this.resource}/report/products/${id}/${is_garage}`,
                "_blank"
            );
            // window.open(`/${this.resource}/report/products/${id}`, '_blank');
        },
        clickDownloadReportCash(id, type) {
            if (type == "excel") {
                window.open(
                    `/${this.resource}/report/cash-excel/${id}`,
                    "_blank"
                );
                return;
            }

            window.open(`/${this.resource}/report/products/${id}`, "_blank");
        },
        clickDownloadReportIncomeEgress(id) {
            window.open(
                `/${this.resource}/report-cash-income-egress/${id}`,
                "_blank"
            );
        },
        clickReportSummaryDailyOperations(id) {
            window.open(
                `/cash-reports/summary-daily-operations/${id}`,
                "_blank"
            );
        },
        clickReportPaymentsAssociatedCash(id) {
            window.open(
                `/cash-reports/payments-associated-cash/${id}`,
                "_blank"
            );
        },
        clickReportCashWithPayments(id) {
            window.open(`/cash-reports/general-with-payments/${id}`, "_blank");
        },
        clickCashPaymentReportExcel(id) {
            window.open(
                `/cash-reports/cash-payment-report-excel/${id}`,
                "_blank"
            );
        }
    }
};
</script>
