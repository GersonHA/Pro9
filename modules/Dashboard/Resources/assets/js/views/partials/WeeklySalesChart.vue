<template>
  <section class="card card-dashboard ws-panel">
    <div class="card-body">
      <div class="ws-head">
        <div>
          <h5 class="ws-title m-0">Ventas · semana</h5>
          <small class="text-muted">Barras sólidas = esta semana · gris = semana anterior</small>
        </div>
        <span v-if="variation !== null" class="ws-badge" :class="variation >= 0 ? 'is-up' : 'is-down'">
          {{ variation >= 0 ? "+" : "" }}{{ variation.toFixed(0) }}% vs sem. ant.
        </span>
      </div>
      <apexchart type="bar" height="300" :options="chartOptions" :series="series"></apexchart>
    </div>
  </section>
</template>

<script>
export default {
  name: "WeeklySalesChart",
  data() {
    return {
      labels: [],
      current: [],
      previous: [],
      barColor: "#0f766e",
    };
  },
  mounted() {
    this.resolveColor();
    this.fetchData();
  },
  computed: {
    series() {
      return [
        { name: "Esta semana", data: this.current },
        { name: "Semana anterior", data: this.previous },
      ];
    },
    variation() {
      const sumCurrent = this.current.reduce((a, b) => a + Number(b), 0);
      const sumPrevious = this.previous.reduce((a, b) => a + Number(b), 0);
      if (!sumPrevious) return null;
      return ((sumCurrent - sumPrevious) / sumPrevious) * 100;
    },
    chartOptions() {
      return {
        chart: { toolbar: { show: false }, fontFamily: "inherit" },
        colors: [this.barColor, "#e2e8f0"],
        plotOptions: { bar: { columnWidth: "55%", borderRadius: 4 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: "#eef0f3", strokeDashArray: 4 },
        xaxis: {
          categories: this.labels,
          axisBorder: { show: false },
          axisTicks: { show: false },
          labels: { style: { colors: "#9ca3af", fontSize: "12px" } },
        },
        yaxis: {
          labels: {
            style: { colors: "#9ca3af", fontSize: "12px" },
            formatter: (val) => this.formatK(val),
          },
        },
        tooltip: {
          y: {
            formatter: (val) =>
              "S/ " +
              Number(val).toLocaleString("es-PE", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }),
          },
        },
      };
    },
  },
  methods: {
    resolveColor() {
      const success = getComputedStyle(document.documentElement).getPropertyValue("--success").trim();
      if (success) this.barColor = success;
    },
    fetchData() {
      this.$http.get("/dashboard/sales-week").then((response) => {
        const data = response.data;
        this.labels = data.labels || [];
        this.current = data.current || [];
        this.previous = data.previous || [];
      });
    },
    formatK(val) {
      const num = Number(val) || 0;
      if (Math.abs(num) >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, "") + "K";
      return num.toFixed(0);
    },
  },
};
</script>

<style scoped>
.ws-panel {
  height: 100%;
}
.ws-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}
.ws-title {
  font-size: 1.05rem;
  font-weight: 600;
}
.ws-badge {
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}
.ws-badge.is-up {
  color: var(--success);
  background: color-mix(in srgb, var(--success) 12%, transparent);
}
.ws-badge.is-down {
  color: var(--danger);
  background: color-mix(in srgb, var(--danger) 12%, transparent);
}
</style>
