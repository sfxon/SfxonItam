import { createApp } from 'vue'
import App from './ManufacturerEditor.vue'

const el = document.getElementById('sfxonitammanufacturereditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitammanufacturereditor')
