import { createApp } from 'vue'
import App from './DeviceEditor.vue'

const el = document.getElementById('sfxonitamdeviceeditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitamdeviceeditor')
