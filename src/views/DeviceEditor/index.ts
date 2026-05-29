import { createApp } from 'vue'
import App from './DeviceEditor.vue'

const el = document.getElementById('sfxonitamdeviceeditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')

const app = createApp(App, {
    entityDefinitions,
})
app.mount('#sfxonitamdeviceeditor')
