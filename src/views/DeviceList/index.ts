import { createApp } from 'vue'
import App from './DeviceList.vue'

const el = document.getElementById('sfxonitam')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitam')
