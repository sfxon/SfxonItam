import { createApp } from 'vue'
import App from './ItamUserEditor.vue'

const el = document.getElementById('sfxonitamitamusereditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitamitamusereditor')
