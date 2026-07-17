<template>
  <section class="card card-dashboard tc-panel">
    <div class="card-body">
      <div class="tc-head">
        <div class="tc-title-row">
          <h5 class="tc-title m-0">Top clientes</h5>
          <span v-if="items.length > 0" class="tc-top-badge">TOP {{ Math.min(items.length, 10) }}</span>
        </div>
        <small class="text-muted">Por monto vendido</small>
      </div>

      <ul class="tc-list">
        <li v-for="(row, index) in items" :key="index" class="tc-item">
          <span class="tc-rank text-muted">{{ index + 1 }}</span>
          <div class="tc-body">
            <div class="tc-row">
              <span class="tc-name text-truncate">{{ row.name }}</span>
              <span class="tc-total">S/ {{ row.total | tcMoney }}</span>
            </div>
            <div class="tc-bar">
              <span class="tc-bar-fill" :style="{ width: barWidth(row.total) }"></span>
            </div>
            <small class="tc-meta text-muted">
              {{ row.number || '—' }} · {{ row.transaction_quantity }} {{ row.transaction_quantity === 1 ? 'transacción' : 'transacciones' }}
            </small>
          </div>
        </li>
      </ul>

      <div v-if="!items.length" class="tc-empty text-muted text-center">
        Sin clientes en el periodo.
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "TopCustomers",
  props: {
    items: { type: Array, default: () => [] },
  },
  computed: {
    maxTotal() {
      return this.items.reduce((max, row) => Math.max(max, Number(row.total) || 0), 0);
    },
  },
  methods: {
    barWidth(total) {
      if (!this.maxTotal) return "0%";
      return Math.max((Number(total) / this.maxTotal) * 100, 3) + "%";
    },
  },
  filters: {
    tcMoney(value) {
      const num = Number(value);
      if (!Number.isFinite(num)) return "0.00";
      if (Math.abs(num) >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, "") + "M";
      if (Math.abs(num) >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, "") + "K";
      return num.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
  },
};
</script>

<style scoped>
.tc-panel { height: 100%; }
.tc-head {
  align-items: flex-start;
  display: flex;
  flex-direction: column;
  margin-bottom: 1rem;
}
.tc-title-row {
  align-items: center;
  display: flex;
  gap: 0.5rem;
  justify-content: space-between;
  width: 100%;
}
.tc-title { font-size: 1.05rem; font-weight: 600; }
.tc-top-badge {
  background: #f1f2f5;
  border-radius: 999px;
  color: #5a6068;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.03em;
  padding: 0.18rem 0.55rem;
}
.tc-list {
  list-style: none;
  margin: 0;
  max-height: 425px;
  overflow-y: auto;
  padding: 0;
  scrollbar-color: rgba(155, 161, 173, 0.32) transparent;
  scrollbar-width: thin;
}
.tc-list::-webkit-scrollbar { width: 5px; }
.tc-list::-webkit-scrollbar-track { background: transparent; }
.tc-list::-webkit-scrollbar-thumb {
  background: rgba(155, 161, 173, 0.28);
  border-radius: 999px;
}
.tc-list:hover::-webkit-scrollbar-thumb { background: rgba(155, 161, 173, 0.46); }
.tc-item {
  align-items: center;
  border-bottom: 1px solid #f1f3f5;
  display: flex;
  gap: 0.75rem;
  padding: 0.6rem 0;
}
.tc-item:last-child { border-bottom: 0; }
.tc-rank {
  flex: 0 0 auto;
  font-size: 0.85rem;
  font-weight: 600;
  text-align: center;
  width: 20px;
}
.tc-body { flex: 1 1 auto; min-width: 0; }
.tc-row {
  display: flex;
  gap: 0.5rem;
  justify-content: space-between;
}
.tc-name { font-weight: 500; min-width: 0; }
.tc-total { flex-shrink: 0; font-weight: 700; }
.tc-bar {
  background: #f1f3f5;
  border-radius: 3px;
  height: 5px;
  margin: 6px 0 4px;
  overflow: hidden;
}
.tc-bar-fill {
  background: var(--primary);
  border-radius: 3px;
  display: block;
  height: 100%;
}
.tc-meta { font-size: 0.72rem; }
.tc-empty { padding: 2rem 0; }
</style>