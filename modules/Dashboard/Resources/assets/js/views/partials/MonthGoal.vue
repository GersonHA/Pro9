<template>
  <section class="card card-dashboard mg-panel">
    <div class="card-body">
      <div class="mg-head">
        <h5 class="mg-title m-0">Meta del mes</h5>
        <small class="text-muted">Faltan S/ {{ remaining | mgMoney }}</small>
      </div>

      <apexchart type="radialBar" height="260" :options="chartOptions" :series="[chartPercent]"></apexchart>

      <div class="mg-foot text-center">
        <div class="mg-detail">S/ {{ sales | mgMoney }} de S/ {{ goal | mgMoney }}</div>
        <small class="text-muted">A este ritmo cierras el mes en ~S/ {{ projected | mgMoney }}</small>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "MonthGoal",
  data() {
    return {
      goal: 0,
      sales: 0,
      percent: 0,
      remaining: 0,
      projected: 0,
      arcColor: "#0e2a33",
    };
  },
  mounted() {
    this.resolveColor();
    this.fetchData();
  },
  computed: {
    chartPercent() {
      return Math.min(Number(this.percent) || 0, 100);
    },
    chartOptions() {
      return {
        chart: { fontFamily: "inherit", sparkline: { enabled: true } },
        colors: [this.arcColor],
        plotOptions: {
          radialBar: {
            startAngle: -110,
            endAngle: 110,
            hollow: { size: "62%" },
            track: { background: "#e9edf1", strokeWidth: "100%" },
            dataLabels: {
              name: { show: false },
              value: {
                offsetY: 6,
                fontSize: "28px",
                fontWeight: 700,
                formatter: () => (Number(this.percent) || 0).toFixed(0) + "%",
              },
            },
          },
        },
        stroke: { lineCap: "round" },
      };
    },
  },
  methods: {
    resolveColor() {
      const primary = getComputedStyle(document.documentElement).getPropertyValue("--primary").trim();
      if (primary) this.arcColor = primary;
    },
    fetchData() {
      this.$http.get("/dashboard/month-goal").then((response) => {
        const data = response.data;
        this.goal = data.goal || 0;
        this.sales = data.sales || 0;
        this.percent = data.percent || 0;
        this.remaining = data.remaining || 0;
        this.projected = data.projected || 0;
      });
    },
  },
  filters: {
    mgMoney(value) {
      return (Number(value) || 0).toLocaleString("es-PE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },
  },
};
</script>

<style scoped>
.mg-panel {
  height: 100%;
}
.mg-head {
  margin-bottom: 0.5rem;
}
.mg-title {
  font-size: 1.05rem;
  font-weight: 600;
}
.mg-foot {
  margin-top: 0.5rem;
}
.mg-detail {
  font-weight: 600;
}
</style>
