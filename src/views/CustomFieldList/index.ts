import { createApp } from 'vue'
import App from './CustomFieldList.vue'

const el = document.getElementById('sfxonitam')
const customFieldGroupId = parseInt(el.dataset.customFieldGroupId)
let customFieldGroup = {}

try {
    customFieldGroup = JSON.parse(el.dataset.customFieldGroup)
} catch (e) {
    console.error('Fehler beim Parsen von customFieldGroup:', e)
}

const app = createApp(App, {
    customFieldGroupId,
    customFieldGroup,
})

app.mount('#sfxonitam')
