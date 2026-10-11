import { createApp } from 'vue'
import App from './MerchantList.vue'

const el = document.getElementById('sfxonitam')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    customFields,
})
app.mount('#sfxonitam')
