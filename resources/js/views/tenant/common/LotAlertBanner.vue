<template>
    <div v-if="alerts.length" class="lot-alert-banner">
        <div v-for="alert in alerts" :key="alert.id" class="lot-alert-item">
            <i class="fas fa-exclamation-triangle lot-alert-icon"></i>
            <span class="lot-alert-text">{{ alert.message }}</span>
            <button class="lot-alert-btn" @click="markSeen(alert.id)">Entendido</button>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            alerts: [],
            interval: null,
            consecutiveErrors: 0,
        }
    },
    mounted() {
        this.fetchAlerts()
        this.interval = setInterval(this.fetchAlerts, 30000)
    },
    beforeDestroy() {
        clearInterval(this.interval)
    },
    methods: {
        async fetchAlerts() {
            try {
                const { data } = await axios.get('/lot-alerts/pending')
                this.alerts = data
                this.consecutiveErrors = 0
            } catch (e) {
                const status = e.response ? e.response.status : null

                // Sesión expirada → detener polling de inmediato.
                // No tiene sentido seguir golpeando el servidor sin autenticación.
                if (status === 401 || status === 403) {
                    clearInterval(this.interval)
                    this.interval = null
                    return
                }

                // Para cualquier otro error (500, red caída, etc.)
                // detener después de 3 fallos consecutivos.
                this.consecutiveErrors++
                if (this.consecutiveErrors >= 3) {
                    clearInterval(this.interval)
                    this.interval = null
                }
            }
        },
        async markSeen(id) {
            try {
                await axios.patch(`/lot-alerts/${id}/seen`)
                this.alerts = this.alerts.filter(a => a.id !== id)
            } catch (e) {}
        },
    },
}
</script>

<style scoped>
.lot-alert-banner {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: 440px;
}
.lot-alert-item {
    background: #e65100;
    color: #fff;
    padding: 14px 16px;
    border-radius: 8px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
    font-size: 13px;
    line-height: 1.5;
}
.lot-alert-icon {
    margin-top: 2px;
    flex-shrink: 0;
    font-size: 15px;
}
.lot-alert-text {
    flex: 1;
}
.lot-alert-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.5);
    color: #fff;
    border-radius: 4px;
    padding: 4px 12px;
    cursor: pointer;
    white-space: nowrap;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
    transition: background 0.15s;
}
.lot-alert-btn:hover {
    background: rgba(255, 255, 255, 0.35);
}
</style>
