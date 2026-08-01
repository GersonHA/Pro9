<template>
  <section class="card card-dashboard ls-panel">
    <div class="card-body mt-0 ls-body">
      <div class="ls-head">
        <h5 class="ls-title m-0">Stock por agotarse</h5>
        <small class="text-muted">{{ total }} {{ total === 1 ? "producto" : "productos" }} con 10 o menos en stock</small>
      </div>

      <!-- El wrap toma el alto que dejan los vecinos de la fila; la lista va
           en absoluto adentro para no empujar el alto de la tarjeta. -->
      <div v-if="items.length" class="ls-list-wrap">
        <ul ref="listWrap" class="ls-list stock">
          <li v-for="(row, index) in visibleItems" :key="index" class="ls-item">
            <span class="ls-name text-truncate">{{ row.product }}</span>
            <span class="ls-ratio">{{ row.stock | lsNum }}</span>
          </li>
        </ul>
      </div>

      <div v-else class="ls-empty text-muted text-center">Sin productos con 10 o menos en stock.</div>
    </div>
  </section>
</template>

<script>
export default {
  name: "LowStock",
  props: {
    filters: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      items: [],
      // Subconjunto que realmente cabe en el alto del cuadro (sin scroll).
      visibleItems: [],
      total: 0,
      resizeObserver: null,
    };
  },
  mounted() {
    this.fetchData();
  },
  beforeDestroy() {
    this.teardownObserver();
  },
  watch: {
    filters: {
      deep: true,
      handler() {
        this.fetchData();
      },
    },
  },
  methods: {
    fetchData() {
      this.$http.get("/dashboard/low-stock", { params: this.filters || {} }).then((response) => {
        const data = response.data;
        this.items = data.items || [];
        this.total = data.total || 0;
        this.visibleItems = this.items.slice();
        this.$nextTick(() => {
          this.computeVisible();
          this.setupObserver();
        });
      });
    },
    setupObserver() {
      // Recalcula cuántas filas caben cuando el cuadro cambia de tamaño
      // (resize de ventana o un widget vecino que redimensiona la fila).
      if (this.resizeObserver || !this.$refs.listWrap) return;
      if (typeof ResizeObserver !== "undefined") {
        this.resizeObserver = new ResizeObserver(() => this.computeVisible());
        this.resizeObserver.observe(this.$refs.listWrap);
      } else {
        window.addEventListener("resize", this.computeVisible);
      }
    },
    teardownObserver() {
      if (this.resizeObserver) {
        this.resizeObserver.disconnect();
        this.resizeObserver = null;
      } else {
        window.removeEventListener("resize", this.computeVisible);
      }
    },
    // Mide el alto disponible y el de una fila, y recorta la lista al número
    // entero de filas que entran: nunca deja una fila cortada ni scroll.
    computeVisible() {
      const list = this.$refs.listWrap;
      if (!list || !this.items.length) return;

      const firstRow = list.querySelector(".ls-item");
      if (!firstRow) return;

      const rowHeight = firstRow.offsetHeight;
      if (rowHeight <= 0) return;

      const maxRows = Math.max(1, Math.floor(list.clientHeight / rowHeight));
      if (maxRows !== this.visibleItems.length) {
        this.visibleItems = this.items.slice(0, maxRows);
      }
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
  margin-bottom: 0 !important;
}
.ls-body {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.ls-head {
  margin-bottom: 1rem;
  flex-shrink: 0;
}
.ls-title {
  font-size: 1.05rem;
  font-weight: 600;
}
/* Ocupa el alto libre de la tarjeta. min-height es solo el piso para móvil,
   donde la columna va sola y no tiene vecino que le marque el alto. */
.ls-list-wrap {
  position: relative;
  flex: 1 1 auto;
  min-height: 260px;
}
.ls-list {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  list-style: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}
.ls-item {
  align-items: center;
  border-bottom: 1px solid #f1f3f5;
  display: flex;
  gap: 0.75rem;
  justify-content: space-between;
  padding: 0.1rem 0;
  font-size: 14px;
}
.ls-item:last-child {
  border-bottom: 0;
}
.ls-name {
  font-weight: 500;
  min-width: 0;
}
.ls-ratio {
  color: var(--danger);
  flex-shrink: 0;
  font-weight: 700;
}
.ls-empty {
  padding: 2rem 0;
}
</style>
