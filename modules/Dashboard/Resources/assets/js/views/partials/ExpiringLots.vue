<template>
  <section class="card card-dashboard el-panel">
    <div class="card-body">
      <div class="el-head">
        <div class="el-title-row">
          <h5 class="el-title m-0">Lotes por vencer</h5>
          <div class="el-dates">
            <el-date-picker
              v-model="date_start"
              type="date"
              :clearable="false"
              :picker-options="pickerOptionsStart"
              format="dd/MM/yyyy"
              value-format="yyyy-MM-dd"
              @change="getRecords"
              class="el-picker"
            />
            <i class="fas fa-arrow-right el-arrow"></i>
            <el-date-picker
              v-model="date_end"
              type="date"
              :clearable="false"
              :picker-options="pickerOptionsEnd"
              format="dd/MM/yyyy"
              value-format="yyyy-MM-dd"
              @change="getRecords"
              class="el-picker"
            />
          </div>
        </div>
        <small class="text-muted">
          <span v-if="rows.length" class="el-urgent">
            <span class="el-dot is-red"></span>
            {{ urgentCount }} {{ urgentCount === 1 ? 'rojo' : 'rojos' }}
          </span>
          <span v-if="rows.length && warnCount" class="el-urgent el-urgent-sep">
            <span class="el-dot is-amber"></span>
            {{ warnCount }} {{ warnCount === 1 ? 'ámbar' : 'ámbar' }}
          </span>
          <span v-if="!rows.length">Próximos 30 días</span>
        </small>
      </div>

      <div v-if="loading" class="el-loading">Cargando...</div>
      <ul v-else-if="rows.length" class="el-list">
        <li v-for="row in rows" :key="row.id" class="el-row">
          <div class="el-cell el-cell-product text-truncate" :title="row.product">
            {{ row.product }}
          </div>
          <div class="el-cell el-cell-lot">{{ row.lot || '—' }}</div>
          <div class="el-cell el-cell-stock">{{ row.stock }}</div>
          <div class="el-cell el-cell-date" :class="row.status">{{ row.daysLeft }}d</div>
        </li>
      </ul>
      <div v-else class="el-empty text-muted text-center">
        No hay lotes por vencer en este rango.
      </div>
    </div>
  </section>
</template>

<script>
import moment from "moment";

export default {
  name: "ExpiringLots",
  props: {
    establishment_id: { type: [String, Number], default: null },
  },
  data() {
    return {
      loading: false,
      rows: [],
      // Default: próximos 30 días desde hoy (horizonte prospectivo, no mes actual)
      date_start: moment().format("YYYY-MM-DD"),
      date_end: moment().add(30, "days").format("YYYY-MM-DD"),
    };
  },
  computed: {
    urgentCount() {
      return this.rows.filter((r) => r.status === "is-red").length;
    },
    warnCount() {
      return this.rows.filter((r) => r.status === "is-amber").length;
    },
    pickerOptionsStart() {
      return { disabledDate: () => false };
    },
    pickerOptionsEnd() {
      return {
        disabledDate: (t) => moment(t).isBefore(moment(this.date_start), "day"),
      };
    },
  },
  mounted() {
    this.getRecords();
  },
  methods: {
    getRecords() {
      if (!this.establishment_id) return;
      this.loading = true;
      this.$http
        .get(`/dashboard/product-of-due/records`, {
          params: {
            establishment_id: this.establishment_id,
            date_start: this.date_start,
            date_end: this.date_end,
            page: 1,
            limit: 20,
          },
        })
        .then((res) => {
          this.rows = (res.data.data || []).map((r) => {
            const days = moment(r.date).diff(moment(), "days");
            // Semáforo: rojo ≤7d, ámbar ≤30d, ok >30d
            let status = "is-ok";
            if (days <= 7) status = "is-red";
            else if (days <= 30) status = "is-amber";
            return {
              id: r.id,
              product: r.product,
              lot: r.lot,
              stock: r.stock,
              daysLeft: days,
              status,
            };
          });
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
  watch: {
    establishment_id() {
      this.getRecords();
    },
  },
};
</script>

<style scoped>
.el-panel { height: 100%; }
.el-head { margin-bottom: 0.75rem; }
.el-title-row {
  align-items: flex-start;
  display: flex;
  gap: 0.5rem;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}
.el-title { font-size: 1.05rem; font-weight: 600; }
.el-dates {
  align-items: center;
  display: flex;
  flex-shrink: 0;
  gap: 0.4rem;
}
.el-picker { width: 130px; }
.el-arrow {
  color: #98a0aa;
  font-size: 0.7rem;
}
.el-urgent {
  font-weight: 600;
  white-space: nowrap;
}
.el-urgent-sep {
  margin-left: 0.75rem;
}
.el-dot {
  border-radius: 999px;
  display: inline-block;
  height: 8px;
  margin-right: 0.3rem;
  vertical-align: middle;
  width: 8px;
}
.el-dot.is-red { background: var(--danger); }
.el-dot.is-amber { background: var(--warning); }

.el-loading {
  color: #98a0aa;
  padding: 2rem;
  text-align: center;
}
.el-list {
  list-style: none;
  margin: 0;
  max-height: 425px;
  overflow-y: auto;
  padding: 0;
  scrollbar-color: rgba(155, 161, 173, 0.32) transparent;
  scrollbar-width: thin;
}
.el-list::-webkit-scrollbar { width: 5px; }
.el-list::-webkit-scrollbar-track { background: transparent; }
.el-list::-webkit-scrollbar-thumb {
  background: rgba(155, 161, 173, 0.28);
  border-radius: 999px;
}
.el-list:hover::-webkit-scrollbar-thumb { background: rgba(155, 161, 173, 0.46); }
.el-row {
  align-items: center;
  border-bottom: 1px solid #f1f3f5;
  display: flex;
  gap: 0.5rem;
  padding: 0.5rem 0;
}
.el-row:last-child { border-bottom: 0; }
.el-cell { font-size: 0.85rem; }
.el-cell-product { flex: 1 1 auto; min-width: 0; }
.el-cell-lot {
  color: #5a6068;
  flex: 0 0 70px;
  font-family: monospace;
  font-size: 0.75rem;
}
.el-cell-stock {
  color: #5a6068;
  flex: 0 0 50px;
  text-align: right;
}
.el-cell-date {
  background: #f1f3f5;
  border-radius: 4px;
  color: #5a6068;
  flex: 0 0 50px;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  text-align: right;
}
.el-cell-date.is-red {
  background: rgba(220, 53, 69, 0.12);
  color: var(--danger);
}
.el-cell-date.is-amber {
  background: rgba(255, 193, 7, 0.12);
  color: var(--warning);
}
.el-cell-date.is-ok {
  background: rgba(40, 167, 69, 0.12);
  color: var(--success);
}
.el-empty { padding: 2rem 0; }
</style>