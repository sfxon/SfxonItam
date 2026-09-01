import { createApp } from 'vue'
import App from './DeviceTypeEditor.vue'

const el = document.getElementById('sfxonitamdevicetypeeditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitamdevicetypeeditor')
