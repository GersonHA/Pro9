<template>
  <section class="card card-dashboard db-panel">
    <div class="card-body">
      <div class="db-head">
        <h5 class="db-title m-0">¿Quién me debe?</h5>
        <small class="text-muted">S/ {{ total | dbMoney }} por cobrar · {{ count }} {{ count === 1 ? "cliente" : "clientes" }}</small>
      </div>

      <ul class="db-list">
        <li v-for="(row, index) in items" :key="index" class="db-item">
          <div class="db-info">
            <span class="db-name text-truncate">{{ row.customer }}</span>
            <small v-if="row.due_text" class="text-muted">{{ row.due_text }}</small>
          </div>
          <div class="db-right">
            <span class="db-amount">S/ {{ row.total_to_pay | dbMoney }}</span>
            <span class="db-badge" :class="'is-' + row.status">{{ statusLabel(row.status) }}</span>
          </div>
        </li>
      </ul>

      <div v-if="!items.length" class="db-empty text-muted text-center">No hay saldos por cobrar.</div>
    </div>
  </section>
</template>

<script>
export default {
  name: "Debtors",
  data() {
    return {
      items: [],
      total: 0,
      count: 0,
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.$http.get("/dashboard/debtors").then((response) => {
        const data = response.data;
        this.items = data.items || [];
        this.total = data.total || 0;
        this.count = data.count || 0;
      });
    },
    statusLabel(status) {
      const labels = { vencido: "vencido", por_vencer: "por vencer", al_dia: "al día" };
      return labels[status] || status;
    },
  },
  filters: {
    dbMoney(value) {
      return (Number(value) || 0).toLocaleString("es-PE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },
  },
};
</script>

<style scoped>
.db-panel {
  height: 100%;
}
.db-head {
  margin-bottom: 1rem;
}
.db-title {
  font-size: 1.05rem;
  font-weight: 600;
}
.db-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.db-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0;
  border-bottom: 1px solid #f1f3f5;
}
.db-item:last-child {
  border-bottom: 0;
}
.db-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.db-name {
  font-weight: 500;
}
.db-right {
  flex-shrink: 0;
  text-align: right;
}
.db-amount {
  display: block;
  font-weight: 700;
}
.db-badge {
  display: inline-block;
  margin-top: 3px;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
}
.db-badge.is-vencido {
  color: var(--danger);
  background: color-mix(in srgb, var(--danger) 12%, transparent);
}
.db-badge.is-por_vencer {
  color: var(--warning);
  background: color-mix(in srgb, var(--warning) 12%, transparent);
}
.db-badge.is-al_dia {
  color: var(--success);
  background: color-mix(in srgb, var(--success) 12%, transparent);
}
.db-empty {
  padding: 2rem 0;
}
</style>
