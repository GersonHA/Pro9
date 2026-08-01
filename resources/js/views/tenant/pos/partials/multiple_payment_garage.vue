<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" :close-on-click-modal="false" :close-on-press-escape="false" :show-close="false">

        <div class="form-body">
            <div class="row" >
                <div class="col-lg-12">

                    <table>
                    <thead>
                        <tr width="100%">
                            <th v-if="payments.length>0">Método de pago</th>
                            <th v-if="payments.length>0">Destino / Fecha</th>
                            <th v-if="payments.length>0">Referencia</th>
                            <th v-if="payments.length>0">Monto</th>
                            <th width="15%">
                                <a v-if="!isCredit" href="#" @click.prevent="clickAddPayment()" class="text-center font-weight-bold text-info">[+ Agregar]</a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in payments" :key="index">
                            <td>
                                <div class="form-group mb-2 mr-2">
                                    <el-select v-model="row.payment_method_type_id" @change="changeMethodType(row)">
                                        <el-option v-for="option in getAvailableMethods(index)" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                    </el-select>
                                </div>
                            </td>
                            <td>
                                <div class="form-group mb-2 mr-2">
                                    <el-select v-if="!isCreditMethod(row.payment_method_type_id)" v-model="row.payment_destination_id" filterable :disabled="row.payment_destination_disabled">
                                        <el-option v-for="option in payment_destinations" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                    </el-select>

                                    <el-date-picker v-else v-model="row.date_of_payment" type="date" value-format="yyyy-MM-dd" :clearable="false" style="width: 100%;"></el-date-picker>
                                </div>
                            </td>
                            <td>
                                <div class="form-group mb-2 mr-2"  >
                                    <el-input v-model="row.reference" placeholder="Opcional"></el-input>
                                </div>
                            </td>
                            <td>
                                <div class="form-group mb-2 mr-2" >
                                    <el-input v-model="row.payment" @input="calculateMixed(index)"></el-input>
                                </div>
                            </td>
                            <td class="series-table-actions text-center">
                                <button v-if="!isCredit" type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickCancel(index)">
                                    <i class="fa fa-trash"></i>
                                </button>
                                <span v-else-if="isCredit && isMixed && index === 0" class="badge bg-info text-white" style="font-size:10px;">INICIAL</span>
                                <span v-else-if="isCredit && isMixed && index === 1" class="badge bg-warning text-white" style="font-size:10px;">A FINANCIAR</span>
                                <span v-else-if="isCredit && !isMixed" class="badge bg-warning text-white" style="font-size:10px;">CRÉDITO</span>
                            </td>
                            <br>
                        </tr>
                    </tbody>
                </table>

                </div>
            </div>
        </div>

        <div class="form-actions text-right pt-2">
            <el-button @click.prevent="close()">Cerrar</el-button>
        </div>
    </el-dialog>
</template>

<script>
    export default {
        props: ['showDialog', 'payments', 'total', 'isCredit', 'isMixed'],
        data() {
            return {
                titleDialog: 'Pagos',
                loading: false,
                errors: {},
                form: {},
                company: {},
                configuration: {},
                activeName: 'first',
                payment_method_types:[],
                payment_destinations: [],
                cards_brand:[],
            }
        },
        computed: {
            cashMethods() {
                return this.payment_method_types.filter(m => m.is_credit == 0 || !m.is_credit);
            },
            creditMethods() {
                return this.payment_method_types.filter(m => m.is_credit == 1);
            }
        },
        async created() {
            await this.$http.get(`/pos/payment_tables`)
                .then(response => {
                    this.payment_method_types = response.data.payment_method_types
                    this.cards_brand = response.data.cards_brand
                    this.payment_destinations = response.data.payment_destinations
                    this.getFormPosLocalStorage()
                })
        },
        methods: {
            getFormPosLocalStorage(){
                let form_pos = localStorage.getItem('form_pos_garage');
                form_pos = JSON.parse(form_pos)
                if (form_pos) {
                    if(form_pos.payments.length > 0){
                        form_pos.payments[0].payment = this.total
                        this.$eventHub.$emit('localSPaymentsGarage', (form_pos.payments))
                        this.$emit('add', form_pos.payments);
                    }
                }
            },
            isCreditMethod(id) {
                let method = this.payment_method_types.find(m => m.id === id);
                return method ? (method.is_credit == 1 || ['05', '08', '09'].includes(id)) : false;
            },
            getAvailableMethods(index) {
                if (this.isCredit && !this.isMixed) {
                    return this.creditMethods;
                } else if (this.isCredit && this.isMixed) {
                    return index === 0 ? this.cashMethods : this.creditMethods;
                } else {
                    return this.cashMethods;
                }
            },
            changeMethodType(row) {
                // Autocalculadora de Fechas
                if (this.isCreditMethod(row.payment_method_type_id)) {
                    let method = this.payment_method_types.find(m => m.id === row.payment_method_type_id);
                    let desc = method.description.toLowerCase();
                    let days = 7; // Por defecto 1 semana para "Crédito"

                    // Busca números en el texto (ej. "a 30 días", "15 dias")
                    let match = desc.match(/(\d+)\s*(dias|días)/);
                    if (match) {
                        days = parseInt(match[1]);
                    }

                    row.date_of_payment = moment().add(days, 'days').format('YYYY-MM-DD');
                }
            },
            create(){
            },
            getNewRow(amount, defaultMethod) {
                let initialDate = moment().format('YYYY-MM-DD');

                // Calcula la fecha inicial al crear la fila
                if (this.isCreditMethod(defaultMethod)) {
                    let method = this.payment_method_types.find(m => m.id === defaultMethod);
                    let desc = method ? method.description.toLowerCase() : '';
                    let days = 7;
                    let match = desc.match(/(\d+)\s*(dias|días)/);
                    if (match) days = parseInt(match[1]);
                    initialDate = moment().add(days, 'days').format('YYYY-MM-DD');
                }

                return {
                    id: null,
                    document_id: null,
                    sale_note_id: null,
                    date_of_payment: initialDate,
                    payment_method_type_id: defaultMethod,
                    payment_destination_id: 'cash',
                    reference: null,
                    payment: amount,
                };
            },
            clickAddPayment(total = 0) {
                // Buscamos '09' (Crédito general) como favorito, si no hay, agarramos el primero
                let favoriteCredit = this.creditMethods.find(m => m.id === '09') ? '09' : (this.creditMethods.length > 0 ? this.creditMethods[0].id : null);

                if (this.isCredit && !this.isMixed) {
                    if (this.payments.length >= 1) return;
                    this.payments.push(this.getNewRow(total, favoriteCredit));

                } else if (this.isCredit && this.isMixed) {
                    if (this.payments.length >= 2) return;
                    let defaultCash = this.cashMethods.length > 0 ? this.cashMethods[0].id : '01';

                    this.payments.push(this.getNewRow(0, defaultCash));
                    this.payments.push(this.getNewRow(total, favoriteCredit));

                } else {
                    let defaultCash = this.cashMethods.length > 0 ? this.cashMethods[0].id : '01';
                    this.payments.push(this.getNewRow(total, defaultCash));
                }

                this.$emit('add', this.payments);
            },
            calculateMixed(index) {
                if (this.isCredit && this.isMixed && this.payments.length === 2) {
                    let totalVenta = parseFloat(this.total);
                    let editado = parseFloat(this.payments[index].payment) || 0;
                    let otroIndex = index === 0 ? 1 : 0;

                    let saldo = totalVenta - editado;
                    this.payments[otroIndex].payment = saldo > 0 ? saldo.toFixed(2) : 0;
                }
                this.$emit('add', this.payments);
            },
            close() {
                this.$emit('update:showDialog', false)
                this.$emit('add', this.payments);
            },
            clickCancel(index) {
                this.payments.splice(index, 1);
                this.$emit('add', this.payments);
            },
            async events() {}
        }
    }
</script>