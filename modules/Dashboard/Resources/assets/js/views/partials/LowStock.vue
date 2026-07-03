<template>
  <section class="card card-dashboard ls-panel">
    <div class="card-body">
      <div class="ls-head">
        <h5 class="ls-title m-0">Stock por agotarse</h5>
        <small class="text-muted">{{ total }} {{ total === 1 ? "producto" : "productos" }} bajo el mínimo</small>
      </div>

      <ul class="ls-list">
        <li v-for="(row, index) in items" :key="index" class="ls-item">
          <span class="ls-name text-truncate">{{ row.product }}</span>
          <span class="ls-ratio">{{ row.stock | lsNum }}/{{ row.stock_min | lsNum }}</span>
        </li>
      </ul>

      <div v-if="!items.length" class="ls-empty text-muted text-center">Sin productos bajo el mínimo.</div>
    </div>
  </section>
</template>

<script>
export default {
  name: "LowStock",
  data() {
    return {
      items: [],
      total: 0,
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    fetchData() {
      this.$http.get("/dashboard/low-stock").then((response) => {
        const data = response.data;
        this.items = data.items || [];
        this.total = data.total || 0;
      });
    },
  },
  filters: {
    lsNum(value) {
      const num = Number(value) || 0;
      return Number.isInteger(num) ? num : num.toLocaleString("es-PE", { maximumFractionDigits: 2 });
    },
  },
};
</script>

<style scoped>
.ls-panel {
  height: 100%;
}
.ls-head {
  margin-bottom: 1rem;
}
.ls-title {
  font-size: 1.05rem;
  font-weight: 600;
}
.ls-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.ls-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0;
  border-bottom: 1px solid #f1f3f5;
}
.ls-item:last-child {
  border-bottom: 0;
}
.ls-name {
  font-weight: 500;
  min-width: 0;
}
.ls-ratio {
  flex-shrink: 0;
  font-weight: 700;
  color: var(--danger);
}
.ls-empty {
  padding: 2rem 0;
}
</style>
