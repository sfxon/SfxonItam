import { createApp } from 'vue'
import App from './QuantityUnitEditor.vue'

const el = document.getElementById('sfxonitamquantityuniteditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitamquantityuniteditor')
