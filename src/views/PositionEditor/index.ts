import { createApp } from 'vue'
import App from './PositionEditor.vue'

const el = document.getElementById('sfxonitampositioneditor')
const entityDefinitions = JSON.parse(el.dataset.entityDefinitions || '{}')
const customFields = JSON.parse(el.dataset.customFields || '[]')

const app = createApp(App, {
    entityDefinitions,
    customFields,
})
app.mount('#sfxonitampositioneditor')
