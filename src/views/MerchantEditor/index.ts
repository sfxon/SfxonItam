import { createApp } from 'vue'
import App from './MerchantEditor.vue'

const el = document.getElementById('sfxonitammerchanteditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitammerchanteditor')
