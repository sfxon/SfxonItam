import { createApp } from 'vue'
import App from './DeviceStatusEditor.vue'

const el = document.getElementById('sfxonitamdevicestatuseditor')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    customFields,
})
app.mount('#sfxonitamdevicestatuseditor')
