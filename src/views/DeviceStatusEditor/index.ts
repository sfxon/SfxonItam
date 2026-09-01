import { createApp } from 'vue'
import App from './DeviceStatusEditor.vue'

const el = document.getElementById('sfxonitamdevicestatuseditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitamdevicestatuseditor')
