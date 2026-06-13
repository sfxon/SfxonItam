import { createApp } from 'vue'
import App from './CustomFieldList.vue'

const el = document.getElementById('sfxonitam')
const customFieldGroupId = parseInt(el.dataset.customFieldGroupId)
const app = createApp(App, {
    customFieldGroupId,
})

app.mount('#sfxonitam')
